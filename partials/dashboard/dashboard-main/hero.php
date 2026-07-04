<?php

/** @var mysqli $conn */

$heroStats = getDashboardHeroStats($conn);

$formatCount = static function (int $value): string {
    return htmlspecialchars(
        number_format($value),
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<section
    class="dashboard-hero"
    aria-label="Patient and admission snapshot">

    <article class="card hero-card">
        <img
            src="public/images/home/hero-1.jpg"
            alt="Doctor consulting a patient"
        >

        <div class="stats">
            <h6>Total Active Patients</h6>
            <h2><?= $formatCount($heroStats['active_patients']) ?></h2>
        </div>
    </article>

    <article class="card hero-card">
        <img
            src="public/images/home/hero-2.jpg"
            alt="Medical examination"
        >

        <div class="stats">
            <div>
                <h6>Admission</h6>
            </div>
            <h2><?= $formatCount($heroStats['admissions']) ?></h2>
        </div>
    </article>

    <article class="card hero-card">
        <img
            src="public/images/home/hero-3.jpg"
            alt="Patient in hospital bed"
        >

        <div class="stats">
            <div>
                <h6>Discharge</h6>
            </div>
            <h2><?= $formatCount($heroStats['discharges']) ?></h2>
        </div>
    </article>

</section>
