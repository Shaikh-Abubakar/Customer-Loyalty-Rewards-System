<?php
declare(strict_types=1);

use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Support\Database;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

try {
    $boot=require __DIR__.'/../bootstrap/cli.php';
    $pdo=Database::connect($boot['config']['database']);
    fwrite(STDOUT,"Create an administrator account for the Customer Loyalty Rewards System.\n");
    fwrite(STDOUT,'Email: ');
    $email=strtolower(trim((string)fgets(STDIN)));
    if ($email==='' || strlen($email)>254 || filter_var($email,FILTER_VALIDATE_EMAIL)===false) {
        throw new RuntimeException('Enter a valid email address.');
    }
    $password=getAdminPassword();
    if (strlen($password)>72 || preg_match_all('/./us',$password,$m)<12) {
        throw new RuntimeException('The password must contain at least 12 characters and no more than 72 bytes.');
    }
    $hash=password_hash($password,PASSWORD_BCRYPT,['cost'=>12]);
    unset($password);
    if (!is_string($hash)) { throw new RuntimeException('Password hashing failed.'); }
    $userId=Database::transaction($pdo,function () use ($pdo,$email,$hash,$boot): int {
        $created=(new UserRepository($pdo))->createAdmin($email,$hash);
        (new AuditLogRepository($pdo))->record(null,'admin_created','user',$created,null,
            'Administrator account created through the controlled CLI procedure.',null,(string)($boot['correlation_id']??''));
        return $created;
    });
    fwrite(STDOUT,"Administrator account created. Sign in through the normal login page.\n");
} catch (PDOException $e) {
    if ((int)($e->errorInfo[1]??0)===1062) { fwrite(STDERR,"That email address is already registered.\n"); exit(1); }
    fwrite(STDERR,"Administrator creation failed. Check the database connection and schema.\n"); exit(1);
} catch (Throwable $e) {
    fwrite(STDERR,'Administrator creation failed: '.$e->getMessage().PHP_EOL);
    exit(1);
}

function getAdminPassword(): string
{
    if (PHP_OS_FAMILY==='Windows') {
        $password=getenv('ADMIN_INITIAL_PASSWORD');
        if (!is_string($password) || $password==='') {
            throw new RuntimeException('On Windows, set ADMIN_INITIAL_PASSWORD from a secure PowerShell Read-Host prompt, run this script, then remove the environment variable.');
        }
        putenv('ADMIN_INITIAL_PASSWORD');
        return $password;
    }
    $state=trim((string)@shell_exec('stty -g 2>/dev/null'));
    if ($state!=='') { @shell_exec('stty -echo'); }
    try {
        fwrite(STDOUT,'Password (input hidden): ');
        $password=(string)fgets(STDIN);
    } finally {
        if ($state!=='') { @shell_exec('stty '.escapeshellarg($state)); }
        fwrite(STDOUT,PHP_EOL);
    }
    return rtrim($password,"\r\n");
}
