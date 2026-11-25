<?php
/** @var string $alertType */
/** @var string $alertMessage */
$alertType = isset($alertType) && in_array($alertType, ['success', 'info', 'warning', 'error'], true)
    ? $alertType
    : 'info';
?>
<div class="alert alert--<?= e($alertType) ?>" role="status">
    <span class="alert__label"><?= e(ucfirst($alertType)) ?></span>
    <span><?= e($alertMessage ?? '') ?></span>
</div>
