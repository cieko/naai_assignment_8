<?php
$currentPage = $_GET['page'] ?? 'dashboard';
?>

<aside class="sidebar">

    <div aria-label="Sidebar navigation">

        <ul>

            <li class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <a
                    href="?page=dashboard"
                    <?= $currentPage === 'dashboard' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-home-5-line"></i>
                </a>
            </li>

            <li class="<?= $currentPage === 'patients' ? 'active' : '' ?>">
                <a
                    href="?page=patients"
                    <?= $currentPage === 'patients' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-team-line"></i>
                </a>
            </li>

            <li class="<?= $currentPage === 'staff' ? 'active' : '' ?>">
                <a
                    href="?page=staff"
                    <?= $currentPage === 'staff' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-nurse-line"></i>
                </a>
            </li>

            <li class="<?= $currentPage === 'visits' ? 'active' : '' ?>">
                <a
                    href="?page=visits"
                    <?= $currentPage === 'visits' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-hospital-line"></i>
                </a>
            </li>

            <li class="<?= $currentPage === 'disease' ? 'active' : '' ?>">
                <a
                    href="?page=disease"
                    <?= $currentPage === 'disease' ? 'aria-current="page"' : '' ?>
                >
                    <i class="ri-bar-chart-box-line"></i>
                </a>
            </li>

        </ul>

    </div>

    <div class="sidebar-footer">

        <a href="#">
            <i class="ri-question-line"></i>
        </a>

    </div>

</aside>