<?php
/** @var array<string, string> $diseaseColors */
/** @var array<int, array<string, mixed>> $distribution */
/** @var Closure $escape */
/** @var int $maxDistributionCount */
/** @var Closure $pluralize */
/** @var array<string, mixed> $trend */
?>

<section class="disease-analytics-grid">
    <article class="card disease-panel disease-panel--trend">
        <div class="disease-panel-header">
            <div class="disease-panel-copy">
                <h3>Disease Trend</h3>
                <p>Admission-driven trendline for the highest-volume diseases from the last six months.</p>
            </div>

            <?php if (!empty($trend['series'])): ?>

                <div class="disease-trend-legend">
                    <?php foreach ($trend['series'] as $item): ?>

                        <span class="disease-trend-legend-item">
                            <span
                                class="disease-trend-legend-swatch"
                                style="background: <?= $escape($diseaseColors[$item['disease_name']] ?? '#2f80ed') ?>;">
                            </span>

                            <?= $escape($item['disease_name']) ?>
                        </span>

                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

        <div
            id="diseaseTrendChart"
            class="disease-chart-canvas">
        </div>
    </article>

    <article class="card disease-panel">
        <div class="disease-panel-header">
            <div class="disease-panel-copy">
                <h3>Disease Distribution</h3>
                <p>Patient record volume with active admission and revenue context for each disease.</p>
            </div>
        </div>

        <?php if (empty($distribution)): ?>

            <div class="disease-inline-empty">
                Add patient disease records to view the distribution.
            </div>

        <?php else: ?>

            <div class="disease-distribution-list">
                <?php foreach ($distribution as $item): ?>

                    <?php
                    $patientTotal = (int)$item['patient_total'];
                    $barWidth = $maxDistributionCount > 0
                        ? max(10, round(($patientTotal / $maxDistributionCount) * 100, 1))
                        : 0;
                    $diseaseColor = $diseaseColors[$item['disease_name']] ?? '#2f80ed';
                    ?>

                    <div class="disease-distribution-item">
                        <div class="disease-distribution-head">
                            <div class="disease-distribution-copy">
                                <strong><?= $escape($item['disease_name']) ?></strong>
                                <span>
                                    <?= $escape(number_format($patientTotal)) ?>
                                    patient
                                    <?= $escape($pluralize($patientTotal, 'record')) ?>
                                    ,
                                    <?= $escape(number_format((int)$item['admission_total'])) ?>
                                    admission
                                    <?= $escape($pluralize((int)$item['admission_total'], 'case')) ?>
                                </span>
                            </div>

                            <span class="disease-distribution-total">
                                <?= $escape(number_format($patientTotal)) ?>
                            </span>
                        </div>

                        <div class="disease-distribution-track">
                            <span
                                class="disease-distribution-fill"
                                style="width: <?= $escape((string)$barWidth) ?>%; background: <?= $escape($diseaseColor) ?>;">
                            </span>
                        </div>

                        <div class="disease-distribution-foot">
                            <span>
                                <?= $escape(number_format((int)$item['active_cases'])) ?>
                                active
                            </span>
                            <span>
                                <?= $escape(formatInrAmount((float)$item['total_revenue'])) ?>
                                revenue
                            </span>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </article>
</section>
