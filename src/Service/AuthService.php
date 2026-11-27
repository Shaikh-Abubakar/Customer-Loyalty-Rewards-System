<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\FraudEventRepository;
use App\Repository\LoginAttemptRepository;
use App\Repository\RegistrationAttemptRepository;
use App\Repository\UserRepository;
use App\Support\Database;
use App\Support\Flash;
use App\Support\Http;
use App\Support\RateLimitGuard;
use App\Support\SessionManager;
use PDO;
use PDOException;
use RuntimeException;

final class AuthService
{
    private UserRepository $users;
    private LoginAttemptRepository $attempts;
    private FraudEventRepository $fraud;
    private ProfileService $profiles;
    private RegistrationAttemptRepository $registrationAttempts;

    public function __construct(private PDO $pdo)
    {
        $this->users = new UserRepository($pdo);
        $this->attempts = new LoginAttemptRepository($pdo);
        $this->fraud = new FraudEventRepository($pdo);
        $this->profiles = new ProfileService($pdo);
        $this->registrationAttempts = new RegistrationAttemptRepository($pdo);
    }

    /** @return array{status:string,errors:array<string,string>,user_id?:int} */
    public function register(array $input, string $ip): array
    {
        return RateLimitGuard::run($this->pdo, [
            ['scope' => 'registration-ip', 'subject' => $ip],
        ], fn (): array => $this->registerWithinLimit($input, $ip));
    }

    /** @return array{status:string,errors:array<string,string>,user_id?:int} */
    private function registerWithinLimit(array $input, string $ip): array
    {
        $limits = app_config('rate_limits', []);
        $maxAttempts = max(1, (int) ($limits['registration_attempts'] ?? 5));
        $window = max(60, (int) ($limits['registration_window_seconds'] ?? 900));
        if ($this->registrationAttempts->countSubmittedByIp($ip, $window) >= $maxAttempts) {
            $this->registrationAttempts->record($ip, 'rate_limited');
            if (!$this->fraud->existsRecent('other', null, $ip, $window)) {
                $this->fraud->record(
                    'other', 'warning', null, $ip, null, null,
                    'Registration attempts reached the configured IP rate limit.'
                );
            }
            return ['status' => 'rate_limited', 'errors' => []];
        }
        // The record contains no email, name, password, or submitted form data.
        $this->registrationAttempts->record($ip, 'submitted');
        $name = is_string($input['name'] ?? null) ? trim($input['name']) : '';
        $email = is_string($input['email'] ?? null) ? strtolower(trim($input['email'])) : '';
        $phone = is_string($input['phone'] ?? null) ? trim($input['phone']) : '';
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirmation = is_string($input['password_confirmation'] ?? null) ? $input['password_confirmation'] : '';
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter your name.';
        } elseif (Http::textLength($name) > 120) {
            $errors['name'] = 'Your name must be 120 characters or fewer.';
        }
        if ($email === '' || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($phone !== '' && (Http::textLength($phone) > 20 || preg_match('/^[0-9+(). -]+$/D', $phone) !== 1 || preg_match('/[0-9]/', $phone) !== 1)) {
            $errors['phone'] = 'Enter a valid phone number or leave this field empty.';
        }
        $passwordLength = Http::textLength($password);
        if ($passwordLength < 12) {
            $errors['password'] = 'Use at least 12 characters.';
        } elseif (strlen($password) > 72) {
            $errors['password'] = 'Use a password no longer than 72 bytes.';
        }
        if ($password !== $confirmation) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }
        if ($errors !== []) {
            return ['status' => 'validation_error', 'errors' => $errors];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        if (!is_string($passwordHash)) {
            throw new RuntimeException('Password hashing failed.');
        }

        try {
            $userId = Database::transaction($this->pdo, function () use ($email, $passwordHash, $name, $phone): int {
                $newUserId = $this->users->createCustomer($email, $passwordHash);
                $this->profiles->createForNewCustomer($newUserId, $name, $phone !== '' ? $phone : null);
                return $newUserId;
            });
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                return ['status' => 'duplicate', 'errors' => []];
            }
            throw $exception;
        }

        $this->establishSession($userId);
        return ['status' => 'success', 'errors' => [], 'user_id' => $userId];
    }

    /** @return array{status:string,role?:string,user_id?:int} */
    public function login(string $emailInput, string $password, string $ip): array
    {
        $emailKey = strtolower(trim($emailInput));
        if (strlen($emailKey) > 254 || $emailKey === '') {
            $emailKey = 'invalid-email-input';
        }
        return RateLimitGuard::run($this->pdo, [
            ['scope' => 'login-ip', 'subject' => $ip],
            ['scope' => 'login-account', 'subject' => $emailKey !== '' ? $emailKey : 'empty-email'],
        ], fn (): array => $this->loginWithinLimit($emailInput, $password, $ip));
    }

    /** @return array{status:string,role?:string,user_id?:int} */
    private function loginWithinLimit(string $emailInput, string $password, string $ip): array
    {
        $email = strtolower(trim($emailInput));
        $emailIsValidForLookup = $email !== '' && strlen($email) <= 254
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $user = $emailIsValidForLookup ? $this->users->findByEmail($email) : null;
        $userId = is_array($user) ? (int) $user['id'] : null;
        $limits = app_config('rate_limits', []);
        $maxFailures = max(1, (int) ($limits['login_failures'] ?? 5));
        $window = max(60, (int) ($limits['login_window_seconds'] ?? 900));

        $ipFailures = $this->attempts->countFailuresByIp($ip, $window);
        $accountFailures = $userId !== null ? $this->attempts->countFailuresByUser($userId, $window) : 0;
        if ($ipFailures >= $maxFailures || $accountFailures >= $maxFailures) {
            if (!$this->fraud->existsRecent('login_bruteforce', $userId, $ip, $window)) {
                $this->fraud->record(
                    'login_bruteforce', 'warning', $userId, $ip, null, null,
                    'Login attempts exceeded the configured failure threshold.'
                );
            }
            return ['status' => 'locked'];
        }

        // Use a fixed valid hash for unknown emails and oversized passwords. The
        // 72-byte cap also avoids bcrypt's historical input truncation behaviour.
        $dummyHash = '$2y$12$hXW4OYMu9K9jBvnxQqc/nezB.4P.lNc.EtpQPHpL5xBEqq.YxznsG';
        $storedHash = is_array($user) ? (string) $user['password_hash'] : $dummyHash;
        $passwordWithinLimit = strlen($password) <= 72;
        $passwordMatches = password_verify($passwordWithinLimit ? $password : 'oversized-password-rejected',
            $passwordWithinLimit ? $storedHash : $dummyHash);

        if (!$passwordMatches || !is_array($user) || ($user['status'] ?? '') !== 'active') {
            $this->attempts->record($userId, $ip, false);
            return ['status' => 'invalid'];
        }

        $loginAccount = Database::transaction($this->pdo, function () use ($userId, $ip, $password): ?array {
            if ($userId === null) {
                return null;
            }
            $locked = $this->users->lockForLogin($userId);
            if ($locked === null || ($locked['status'] ?? '') !== 'active'
                || !password_verify($password, (string) ($locked['password_hash'] ?? ''))) {
                $this->attempts->record($userId, $ip, false);
                return null;
            }
            if (password_needs_rehash((string) $locked['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                if (is_string($newHash)) {
                    $this->users->updatePasswordHash($userId, $newHash);
                }
            }
            $this->users->updateLastLogin($userId);
            $this->attempts->record($userId, $ip, true);
            return $locked;
        });

        if (!is_array($loginAccount)) {
            return ['status' => 'invalid'];
        }

        $this->establishSession((int) $userId);
        return [
            'status' => 'success',
            'role' => (string) $loginAccount['role'],
            'user_id' => (int) $userId,
        ];
    }

    public function establishSession(int $userId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('An active session is required for login.');
        }
        $_SESSION = [];
        SessionManager::regenerateId();
        $_SESSION['auth_user_id'] = $userId;
        $_SESSION['auth_logged_in_at'] = time();
        $_SESSION['_last_activity'] = time();
    }

    public function logout(): void
    {
        self::logoutCurrentSession();
    }

    public static function logoutCurrentSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $params = session_get_cookie_params();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public function clearInactiveSession(): void
    {
        $this->logout();
    }
}
