<?php
$currentPage = $_GET['page'] ?? 'dashboard';
?>

<aside class="sidebar">

    <nav aria-label="Sidebar navigation">

        <ul>

            <li class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <a
                    href="?page=dashboard"
                    aria-label="Dashboard overview"
                    <?= $currentPage === 'dashboard' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-home-5-line" aria-hidden="true"></i>
                    <span class="sr-only">Dashboard overview</span>
                </a>
            </li>

            <li class="<?= $currentPage === 'patients' ? 'active' : '' ?>">
                <a
                    href="?page=patients"
                    aria-label="Patient management"
                    <?= $currentPage === 'patients' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-team-line" aria-hidden="true"></i>
                    <span class="sr-only">Patient management</span>
                </a>
            </li>

            <li class="<?= $currentPage === 'staff' ? 'active' : '' ?>">
                <a
                    href="?page=staff"
                    aria-label="Staff management"
                    <?= $currentPage === 'staff' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-nurse-line" aria-hidden="true"></i>
                    <span class="sr-only">Staff management</span>
                </a>
            </li>

            <li class="<?= $currentPage === 'visits' ? 'active' : '' ?>">
                <a
                    href="?page=visits"
                    aria-label="Visit management"
                    <?= $currentPage === 'visits' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-hospital-line" aria-hidden="true"></i>
                    <span class="sr-only">Visit management</span>
                </a>
            </li>

            <li class="<?= $currentPage === 'disease' ? 'active' : '' ?>">
                <a
                    href="?page=disease"
                    aria-label="Disease trend analysis"
                    <?= $currentPage === 'disease' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-bar-chart-box-line" aria-hidden="true"></i>
                    <span class="sr-only">Disease trend analysis</span>
                </a>
            </li>

        </ul>

    </nav>

    <div class="sidebar-footer">

        <a href="#main-content" aria-label="Skip to main content">
            <i class="ri-question-line" aria-hidden="true"></i>
            <span class="sr-only">Skip to main content</span>
        </a>

    </div>

</aside>
