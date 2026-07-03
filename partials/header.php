<?php
$headerSearchValue = isCurrentPage('patients')
    ? getSearchTerm('search')
    : '';
?>

<header>

    <div class="logo">
        <img
            src="public/images/icon.png"
            alt="Analytics Icon"
        >
        <span>Analytics</span>
    </div>

    <nav aria-label="Main navigation">
        <ul>
            <li class="<?= isActive('dashboard') ?>">
                <a 
                    href="?page=dashboard"
                    <?= isCurrentPage('dashboard') ? 'aria-current="page"' : '' ?>
                >
                    Overview
                </a>
            </li>
            <li class="<?= isActive('staff') ?>">
                <a 
                    href="?page=staff"
                    <?= isCurrentPage('staff') ? 'aria-current="page"' : '' ?>
                >
                    Staff Management
                </a>
            </li>
            <li class="<?= isActive('disease') ?>">
                <a 
                    href="?page=disease"
                    <?= isCurrentPage('disease') ? 'aria-current="page"' : '' ?>
                >
                    Disease Trend
                </a>
            </li>
            <li class="<?= isActive('visits') ?>">
                <a 
                    href="?page=visits"
                    <?= isCurrentPage('visits') ? 'aria-current="page"' : '' ?>
                >
                    Visit History
                </a>
            </li>
        </ul>
    </nav>

    <div class="actions">

        <form
            action=""
            method="get"
            class="search"
            role="search"
            aria-label="Search patient">
            <input
                type="hidden"
                name="page"
                value="patients">

            <i class="ri-search-line" aria-hidden="true"></i>

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($headerSearchValue, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search Patient"
                aria-label="Search patient"
            >
        </form>

        <img
            class="profile"
            src="public/images/user.png"
            alt="User profile"
        >

    </div>

</header>
