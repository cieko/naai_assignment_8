<?php

$flashData = $flash ?? null;

if (
    !is_array($flashData)
    || empty($flashData['message'])
) {
    return;
}

$flashType = htmlspecialchars(
    (string)($flashData['type'] ?? 'info'),
    ENT_QUOTES,
    'UTF-8'
);

$flashMessage = htmlspecialchars(
    (string)$flashData['message'],
    ENT_QUOTES,
    'UTF-8'
);

$isAssertiveFlash = $flashType === 'error';

?>

<div
    class="app-flash app-flash--<?= $flashType ?>"
    role="<?= $isAssertiveFlash ? 'alert' : 'status' ?>"
    aria-live="<?= $isAssertiveFlash ? 'assertive' : 'polite' ?>"
    aria-atomic="true">
    <span><?= $flashMessage ?></span>
</div>
