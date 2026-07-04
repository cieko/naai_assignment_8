<?php
/** @var array<string, mixed> $departmentBreakdown */
/** @var array<string, string> $departmentColors */
/** @var array<string, string> $diseaseColors */
/** @var Closure $escape */
/** @var Closure $pluralize */
?>

<article class="card disease-panel disease-panel--full">
    <div class="disease-panel-header">
        <div class="disease-panel-copy">
            <h3>Disease By Department</h3>
            <p>How the highest-volume diseases are distributed across departments based on admission records.</p>
        </div>
    </div>

    <?php if (empty($departmentBreakdown['rows'])): ?>

        <div class="disease-inline-empty">
            Add admissions linked to patients and doctors to view department activity.
        </div>

    <?php else: ?>

        <div class="disease-department-grid">
            <?php foreach ($departmentBreakdown['rows'] as $row): ?>

                <?php
                $diseaseName = $row['disease_name'];
                $diseaseAccent = $diseaseColors[$diseaseName] ?? '#2f80ed';
                $departmentCount = count($row['segments']);
                ?>

                <article
                    class="disease-department-card"
                    style="--disease-accent: <?= $escape($diseaseAccent) ?>;">
                    <div class="disease-department-card__header">
                        <div class="disease-department-card__copy">
                            <strong><?= $escape($diseaseName) ?></strong>
                            <span>
                                Across
                                <?= $escape(number_format($departmentCount)) ?>
                                <?= $escape($pluralize($departmentCount, 'department')) ?>
                            </span>
                        </div>

                        <span class="disease-department-card__total">
                            <?= $escape(number_format((int)$row['total_cases'])) ?>
                            <?= $escape($pluralize((int)$row['total_cases'], 'case')) ?>
                        </span>
                    </div>

                    <div class="disease-department-tiles">
                        <?php foreach ($row['segments'] as $segment): ?>

                            <?php $departmentName = $segment['department_name']; ?>

                            <article
                                class="disease-department-tile"
                                style="--department-accent: <?= $escape($departmentColors[$departmentName] ?? '#64748b') ?>;">
                                <div class="disease-department-tile__top">
                                    <span class="disease-department-tile__name">
                                        <span
                                            class="disease-department-legend-swatch"
                                            aria-hidden="true">
                                        </span>
                                        <?= $escape($departmentName) ?>
                                    </span>

                                    <strong>
                                        <?= $escape(number_format((int)$segment['total_cases'])) ?>
                                    </strong>
                                </div>

                                <div class="disease-department-tile__meta">
                                    <span>
                                        <?= $escape(number_format((int)$segment['total_cases'])) ?>
                                        <?= $escape($pluralize((int)$segment['total_cases'], 'case')) ?>
                                    </span>

                                    <span><?= $escape(number_format((float)$segment['percentage'], 1)) ?>%</span>
                                </div>
                            </article>

                        <?php endforeach; ?>
                    </div>
                </article>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</article>
