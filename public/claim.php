<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Service\ClaimService;
use App\Support\Flash;
use App\Support\Http;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (!$app['pdo'] instanceof PDO) {
    throw new RuntimeException('Database is not available.');
}
$pdo = $app['pdo'];
// Preserve only the fixed claim destination; never carry a submitted code through login.
if ((int) ($_SESSION['auth_user_id'] ?? 0) < 1) {
    Flash::set('info', 'Sign in to continue. You can then rescan or type your product code.');
    redirect_to('/login.php?return=claim#');
}
$currentUser = AuthMiddleware::requireCustomer($pdo);
$headerRole = 'customer';
Http::privateNoStore();

if (request_method_is('POST')) {
    require_method('POST');
    require_csrf();
    $input = is_string($_POST['code'] ?? null) ? $_POST['code'] : '';
    $result = (new ClaimService($pdo))->claim((int) $currentUser['id'], $input, Http::clientIp());
    switch ($result['status']) {
        case 'success':
            Flash::set('success', number_format((int) $result['points']) . ' points added. Your new balance is ' . number_format((int) $result['balance']) . ' points.');
            redirect_to('/history.php');
        case 'invalid_format':
            Flash::set('error', 'That code is not in the right format. Check it and try again.');
            break;
        case 'already_claimed_by_you':
            Flash::set('info', 'You have already claimed this code.');
            break;
        case 'rate_limited':
            Flash::set('warning', 'Too many attempts. Please try again later.');
            break;
        case 'account_inactive':
            Flash::set('warning', 'Your account is no longer active. Contact support for help.');
            redirect_to('/login.php');
        default:
            Flash::set('error', 'This code is not valid or has already been used.');
            break;
    }
    redirect_to('/claim.php');
}

$pageTitle = 'Claim a product code';
require dirname(__DIR__) . '/templates/layout/header.php';
?>
<main id="main-content" class="container page-shell page-shell--narrow">
    <section class="panel form-panel" aria-labelledby="page-heading">
        <p class="eyebrow">Earn loyalty points</p>
        <h1 id="page-heading">Claim your product code</h1>
        <p class="muted-copy">Your unique 16-character code is inside an eligible sealed product box, under a scratch-off layer or on an insert.</p>
        <div class="instruction-panel">
            <h2>Before you submit</h2>
            <ol>
                <li>Open an eligible product box and reveal its concealed code.</li>
                <li>Enter all 16 characters. Letter case does not matter.</li>
                <li>Spaces and hyphens are accepted as separators.</li>
                <li>Press Claim points. A code can award points only once.</li>
            </ol>
        </div>
        <form method="post" action="<?= e(url('/claim.php')) ?>" class="stack-form" autocomplete="off">
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="code">16-character product code</label>
                <input id="code" name="code" type="text" inputmode="text" maxlength="32" minlength="16" required
                    autocomplete="off" autocapitalize="characters" spellcheck="false"
                    placeholder="e.g. 2345-6789-ABCD-EFGH" aria-describedby="code-help">
                <span id="code-help" class="form-hint">Allowed characters: 2–9 and A–Z, excluding I, L, O and U. Enter 16 characters after removing spaces and hyphens.</span>
            </div>
            <button type="submit" class="button button--primary">Claim points</button>
        </form>
        <p class="support-note">Opening a QR claim link only fills this field. You must always press the button to submit a claim. If the field does not fill automatically, type the code manually.</p>
    </section>
</main>
<?php if ($app['csp_nonce'] !== ''): ?>
<script nonce="<?= e($app['csp_nonce']) ?>">
(function () {
    var hash = window.location.hash;
    if (!hash) return;
    var fragment = hash.charAt(0) === '#' ? hash.substring(1) : hash;
    var parameters = new URLSearchParams(fragment);
    var value = parameters.get('c');
    var input = document.getElementById('code');
    if (value && input) input.value = value;
    window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
}());
</script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/templates/layout/footer.php'; ?>
