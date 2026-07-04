<?php
/** @var Closure $escape */
/** @var array<string, mixed> $overview */
/** @var Closure $pluralize */
?>

<section
    class="disease-summary"
    aria-label="Disease summary metrics">
    <article class="card disease-stat-card">
        <span class="disease-stat-card__eyebrow">Tracked Patients</span>
        <strong class="disease-stat-card__value">
            <?= $escape(number_format($overview['tracked_patients'])) ?>
        </strong>
        <span class="disease-stat-card__meta">
            <?= $escape(number_format($overview['unique_diseases'])) ?>
            disease
            <?= $escape($pluralize((int)$overview['unique_diseases'], 'category', 'categories')) ?>
        </span>
    </article>

    <article class="card disease-stat-card disease-stat-card--highlight">
        <span class="disease-stat-card__eyebrow">Most Common Disease</span>
        <strong class="disease-stat-card__value disease-stat-card__value--text">
            <?= $escape($overview['top_disease_name']) ?>
        </strong>
        <span class="disease-stat-card__meta">
            <?= $escape(number_format($overview['top_disease_count'])) ?>
            patient
            <?= $escape($pluralize((int)$overview['top_disease_count'], 'record')) ?>
        </span>
    </article>

    <article class="card disease-stat-card">
        <span class="disease-stat-card__eyebrow">Active Admissions</span>
        <strong class="disease-stat-card__value">
            <?= $escape(number_format($overview['active_cases'])) ?>
        </strong>
        <span class="disease-stat-card__meta">Currently under treatment</span>
    </article>

    <article class="card disease-stat-card">
        <span class="disease-stat-card__eyebrow">Discharged Cases</span>
        <strong class="disease-stat-card__value">
            <?= $escape(number_format($overview['discharged_cases'])) ?>
        </strong>
        <span class="disease-stat-card__meta">Completed admissions</span>
    </article>

    <article class="card disease-stat-card">
        <span class="disease-stat-card__eyebrow">Disease Revenue</span>
        <strong class="disease-stat-card__value disease-stat-card__value--currency">
            <?= $escape(formatInrAmount((float)$overview['total_revenue'])) ?>
        </strong>
        <span class="disease-stat-card__meta">Billing linked to disease cases</span>
    </article>
</section>
