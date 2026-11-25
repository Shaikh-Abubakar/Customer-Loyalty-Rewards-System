<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/autoload.php';

use App\Support\CodeFormat;
use App\Support\Hmac;
use App\Support\Http;

$checks = 0;
$failures = 0;
$check = static function (string $name, bool $condition) use (&$checks, &$failures): void {
    $checks++;
    if ($condition) {
        echo "PASS {$name}\n";
        return;
    }
    $failures++;
    echo "FAIL {$name}\n";
};

$sample = '23456789ABCDEFGH';
$check('Approved product-code alphabet has 30 characters', strlen(CodeFormat::ALPHABET) === 30);
$check('Valid 16-character product code accepted', CodeFormat::isValid($sample));
$check('Lowercase code normalised to uppercase', CodeFormat::normalize(strtolower($sample)) === $sample);
$check('Spaces and hyphens removed during normalisation', CodeFormat::normalize('2345-6789 ABCD-EFGH') === $sample);
$check('Code with forbidden zero rejected', !CodeFormat::isValid('03456789ABCDEFGH'));
$check('Code with forbidden I rejected', !CodeFormat::isValid('23456789ABCDEFGI'));
$check('Code with forbidden L rejected', !CodeFormat::isValid('23456789ABCDEFGL'));
$check('Code with forbidden O rejected', !CodeFormat::isValid('23456789ABCDEFGO'));
$check('Code with forbidden U rejected', !CodeFormat::isValid('23456789ABCDEFGU'));
$check('Code of 15 characters rejected', !CodeFormat::isValid(substr($sample, 1)));
$check('Code of 17 characters rejected', !CodeFormat::isValid($sample . '2'));

$secret = random_bytes(32);
$encoded = base64_encode($secret);
$check('32-byte Base64 HMAC secret decoded', Hmac::decodeSecret($encoded) === $secret);
$shortKeyRejected = false;
try {
    Hmac::decodeSecret(base64_encode(random_bytes(31)));
} catch (RuntimeException) {
    $shortKeyRejected = true;
}
$check('HMAC secret shorter than 32 bytes rejected', $shortKeyRejected);
$hashA = Hmac::codeHash($sample, $secret);
$check('HMAC-SHA-256 product digest is 32 raw bytes', strlen($hashA) === 32);
$check('Product HMAC is deterministic', hash_equals($hashA, Hmac::codeHash($sample, $secret)));
$check('Different product codes produce different HMACs', !hash_equals($hashA, Hmac::codeHash($sample . '2', $secret)));
$check('Different secrets produce different HMACs', !hash_equals($hashA, Hmac::codeHash($sample, random_bytes(32))));
$check('Key-check digest is 32 raw bytes', strlen(Hmac::keyCheck($secret)) === 32);
$check('Code HMAC and key-check HMAC are domain-separated', !hash_equals($hashA, Hmac::keyCheck($secret)));

$password = 'college-project-password-2026';
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$check('Password hashing returns a non-plaintext bcrypt hash', is_string($passwordHash) && $passwordHash !== $password && password_get_info($passwordHash)['algoName'] === 'bcrypt');
$check('Password verification accepts the correct password', is_string($passwordHash) && password_verify($password, $passwordHash));
$check('Password verification rejects an incorrect password', is_string($passwordHash) && !password_verify('incorrect-password', $passwordHash));
$dummyHash = '$2y$12$hXW4OYMu9K9jBvnxQqc/nezB.4P.lNc.EtpQPHpL5xBEqq.YxznsG';
$check('Unknown-account timing hash is syntactically valid bcrypt', password_get_info($dummyHash)['algoName'] === 'bcrypt');
$check('Unknown-account timing hash does not verify arbitrary input', !password_verify('not-the-dummy-password', $dummyHash));
$check('UTF-8 name length counts characters rather than bytes', Http::textLength('Éva') === 3);

$repositoryFiles = glob(dirname(__DIR__) . '/src/Repository/*.php') ?: [];
$hasBrokenSingleQuotedNewline = false;
foreach ($repositoryFiles as $repositoryFile) {
    $source = file_get_contents($repositoryFile);
    if (!is_string($source)) {
        $hasBrokenSingleQuotedNewline = true;
        break;
    }
    foreach (token_get_all($source) as $token) {
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING
            && $token[1][0] === "'" && str_contains($token[1], "\\n")) {
            $hasBrokenSingleQuotedNewline = true;
            break 2;
        }
    }
}
$check('Repository SQL has no escaped newline in single-quoted PHP strings', !$hasBrokenSingleQuotedNewline);

printf("%d checks, %d failures.\n", $checks, $failures);
exit($failures === 0 ? 0 : 1);
