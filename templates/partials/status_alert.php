<?php
declare(strict_types=1);
/** @var string $alertType */
/** @var string $alertMessage */
$alertType = isset($alertType) && in_array($alertType, ['success', 'info', 'warning', 'error'], true)
    ? $alertType
    : 'info';
$alertRole = $alertType === 'error' ? 'alert' : 'status';
?>
<div class="alert alert--<?= e($alertType) ?>" role="<?= e($alertRole) ?>" aria-live="<?= $alertType === 'error' ? 'assertive' : 'polite' ?>">
    <span class="alert__label"><?= e(ucfirst($alertType)) ?></span>
    <span><?= e($alertMessage ?? '') ?></span>
</div>
