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

?>

<div class="app-flash app-flash--<?= $flashType ?>">
    <span><?= $flashMessage ?></span>
</div>
