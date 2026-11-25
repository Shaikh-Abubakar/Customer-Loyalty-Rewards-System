<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Repository\UserRepository;
use App\Support\Flash;
use App\Support\Http;
use PDO;

final class AuthMiddleware
{
    public static function requireCustomer(PDO $pdo): array
    {
        Http::privateNoStore();
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
        if ($userId < 1) {
            Flash::set('info', 'Sign in to continue.');
            redirect_to('/login.php');
        }

        $user = (new UserRepository($pdo))->findById($userId);
        if ($user === null || ($user['status'] ?? '') !== 'active') {
            self::resetToGuest('Your session is no longer active. Contact support for help.');
            redirect_to('/login.php');
        }
        if (($user['role'] ?? '') !== 'customer') {
            http_response_code(403);
            self::renderDenied();
            exit;
        }
        return $user;
    }

    public static function requireAdmin(PDO $pdo): array
    {
        Http::privateNoStore();
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
        if ($userId < 1) {
            Flash::set('info', 'Sign in to continue.');
            redirect_to('/login.php');
        }
        $user = (new UserRepository($pdo))->findById($userId);
        if ($user === null || ($user['status'] ?? '') !== 'active') {
            self::resetToGuest('Your session is no longer active. Contact support for help.');
            redirect_to('/login.php');
        }
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            self::renderDenied();
            exit;
        }
        return $user;
    }

    public static function redirectIfAuthenticated(PDO $pdo): void
    {
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
        if ($userId < 1) {
            return;
        }
        $user = (new UserRepository($pdo))->findById($userId);
        if ($user !== null && ($user['status'] ?? '') === 'active') {
            redirect_to(($user['role'] ?? '') === 'admin' ? '/admin/index.php' : '/dashboard.php');
        }
        self::resetToGuest('Your session is no longer active. Contact support for help.');
    }

    private static function resetToGuest(string $message): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        Flash::set('warning', $message);
    }

    private static function renderDenied(): void
    {
        $pageTitle = 'Access denied';
        require APP_ROOT . '/templates/layout/header.php';
        echo '<main id="main-content" class="container page-shell"><section class="panel">'
            . '<h1>Access denied</h1><p>You do not have permission to view this page.</p>'
            . '<p><a class="button button--secondary" href="' . e(url('/')) . '">Return home</a></p>'
            . '</section></main>';
        require APP_ROOT . '/templates/layout/footer.php';
    }
}
