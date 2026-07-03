<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/analytics-crud.php';
require_once __DIR__ . '/includes/patients-crud.php';
require_once __DIR__ . '/includes/visits-crud.php';

$conn = connection();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Analytics</title>

    <link rel="shortcut icon" href="./public/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="./public/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/styles/index.css">

    <script src="https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/vanilla-calendar-pro/index.js" defer></script>
    <script src="./public/js/app.js" defer></script>
</head>

<body>
    <?php include __DIR__ . '/partials/header.php' ?>

    <div class="dashboard-layout">

        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="container">
            <?php

            $page = $_GET['page'] ?? 'dashboard';

            $pages = [
                'dashboard'   => 'partials/dashboard/layout.php',
                'patients'       => 'partials/patients/layout.php',
                'staff'       => 'partials/staff/layout.php',
                'visits'      => 'partials/visits/layout.php',
                'disease'     => 'partials/disease/layout.php',
            ];

            if (!isset($pages[$page])) {
                $page = 'dashboard';
            }

            include __DIR__ . '/' . $pages[$page];
            ?>
        </main>

    </div>
</body>

</html>
