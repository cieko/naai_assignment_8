<?php

/** @var mysqli $conn */

$overview = getDiseaseOverview($conn);
$distribution = getDiseaseDistribution($conn, 6);
$trend = getDiseaseTrendSeries($conn, 6, 4);
$departmentBreakdown = getDiseaseDepartmentBreakdown($conn, 5);
$recentCases = getRecentDiseaseCases($conn, 8);

$escape = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatDate = static function (?string $value, string $empty = 'Still admitted'): string {
    if ($value === null || $value === '' || $value === '0000-00-00') {
        return $empty;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

    return $date instanceof DateTimeImmutable
        ? $date->format('M d, Y')
        : $value;
};

$pluralize = static function (int $count, string $singular, ?string $plural = null): string {
    $resolvedPlural = $plural ?? ($singular . 's');

    return $count === 1 ? $singular : $resolvedPlural;
};

$diseasePalette = [
    '#2f80ed',
    '#06b6d4',
    '#fb7185',
    '#a855f7',
    '#f59e0b',
    '#22c55e',
];

$departmentPalette = [
    '#1d4ed8',
    '#10b981',
    '#f97316',
    '#ec4899',
    '#8b5cf6',
    '#0ea5e9',
];

$diseaseNames = [];

foreach ($distribution as $item) {
    $diseaseNames[] = $item['disease_name'];
}

foreach ($trend['series'] as $item) {
    $diseaseNames[] = $item['disease_name'];
}

foreach ($departmentBreakdown['rows'] as $item) {
    $diseaseNames[] = $item['disease_name'];
}

$diseaseNames = array_values(
    array_unique(
        array_filter($diseaseNames)
    )
);

$diseaseColors = [];

foreach ($diseaseNames as $index => $diseaseName) {
    $diseaseColors[$diseaseName] = $diseasePalette[$index % count($diseasePalette)];
}

$departmentColors = [];

foreach ($departmentBreakdown['departments'] as $index => $department) {
    $departmentName = $department['department_name'];
    $departmentColors[$departmentName] = $departmentPalette[$index % count($departmentPalette)];
}

$maxDistributionCount = 0;

foreach ($distribution as $item) {
    $maxDistributionCount = max(
        $maxDistributionCount,
        (int)$item['patient_total']
    );
}

$trendPayload = [
    'months' => $trend['months'],
    'series' => [],
];

foreach ($trend['series'] as $item) {
    $trendPayload['series'][] = [
        'name' => $item['disease_name'],
        'color' => $diseaseColors[$item['disease_name']] ?? '#2f80ed',
        'values' => array_map(
            static function (array $point): array {
                return [
                    'label' => $point['label'],
                    'value' => (int)$point['value'],
                ];
            },
            $item['values']
        ),
    ];
}

$jsonFlags = JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT;

$paymentBadgeMap = [
    'Paid' => 'paid',
    'Partial' => 'partial',
    'Pending' => 'pending',
];

$statusBadgeMap = [
    'Admitted' => 'admitted',
    'Discharged' => 'discharged',
];

?>

<section class="disease-page">
    <div class="staff-header">
        <div class="headline">
            <h2>Disease Trend Analysis</h2>
            <p>Track disease volume, department concentration, and revenue signals across admissions.</p>
        </div>
    </div>

    <section class="disease-summary">
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
                                            <span class="disease-department-legend-swatch"></span>
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

    <section class="staff-card disease-table-card">
        <div class="disease-table-header">
            <div class="headline">
                <h3>Recent Disease Cases</h3>
                <p>Latest admitted or discharged cases with doctor, department, treatment cost, and payment status.</p>
            </div>
        </div>

        <?php if (empty($recentCases)): ?>

            <div class="staff-empty">
                No disease-linked admission records are available yet.
            </div>

        <?php else: ?>

            <table class="staff-table staff-table--wide">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Disease</th>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Admission</th>
                        <th>Discharge</th>
                        <th>Treatment Cost</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($recentCases as $case): ?>

                        <?php
                        $paymentStatus = (string)$case['payment_status'];
                        $admissionStatus = (string)$case['status'];
                        $paymentClass = $paymentBadgeMap[$paymentStatus] ?? 'pending';
                        $statusClass = $statusBadgeMap[$admissionStatus] ?? 'admitted';
                        ?>

                        <tr>
                            <td><?= $escape($case['patient_name']) ?></td>
                            <td><?= $escape($case['disease_name']) ?></td>
                            <td><?= $escape($case['doctor_name']) ?></td>
                            <td><?= $escape($case['department_name']) ?></td>
                            <td><?= $escape($formatDate($case['admission_date'], 'Not set')) ?></td>
                            <td><?= $escape($formatDate($case['discharge_date'])) ?></td>
                            <td><?= $escape(formatInrAmount((float)$case['amount'])) ?></td>
                            <td>
                                <span class="disease-badge disease-badge--<?= $escape($paymentClass) ?>">
                                    <?= $escape($paymentStatus) ?>
                                </span>
                            </td>
                            <td>
                                <span class="disease-badge disease-badge--<?= $escape($statusClass) ?>">
                                    <?= $escape($admissionStatus) ?>
                                </span>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </section>
</section>

<script>
(() => {
    const trendData = <?= json_encode($trendPayload, $jsonFlags) ?>;

    const bootDiseaseTrendChart = () => {
        if (!window.d3) {
            return;
        }

        const d3 = window.d3;
        const target = document.getElementById('diseaseTrendChart');

        if (!target) {
            return;
        }

        const drawEmptyState = (message) => {
            const empty = document.createElement('div');
            empty.className = 'disease-empty';
            empty.textContent = message;
            target.appendChild(empty);
        };

        const renderChart = () => {
            target.innerHTML = '';

            if (!trendData.series.length) {
                drawEmptyState('Add admissions with linked disease records to view the monthly trend.');
                return;
            }

            const width = Math.max(target.clientWidth, 320);
            const height = target.clientHeight || 320;
            const margin = { top: 18, right: 18, bottom: 42, left: 44 };
            const monthLabels = trendData.months.map((month) => month.label);
            const maxValue = Math.max(
                1,
                d3.max(
                    trendData.series,
                    (series) => d3.max(series.values, (point) => point.value)
                ) || 0
            );

            const svg = d3
                .select(target)
                .append('svg')
                .attr('class', 'disease-trend-svg')
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('preserveAspectRatio', 'none');

            const x = d3
                .scalePoint()
                .domain(monthLabels)
                .range([margin.left, width - margin.right])
                .padding(0.35);

            const y = d3
                .scaleLinear()
                .domain([0, maxValue])
                .nice()
                .range([height - margin.bottom, margin.top]);

            svg
                .append('g')
                .attr('class', 'disease-trend-grid')
                .selectAll('line')
                .data(y.ticks(5))
                .join('line')
                .attr('x1', margin.left)
                .attr('x2', width - margin.right)
                .attr('y1', (tick) => y(tick))
                .attr('y2', (tick) => y(tick));

            svg
                .append('g')
                .attr('class', 'disease-trend-axis')
                .attr('transform', `translate(0, ${height - margin.bottom})`)
                .call(
                    d3.axisBottom(x)
                        .tickSize(0)
                );

            svg
                .append('g')
                .attr('class', 'disease-trend-axis')
                .attr('transform', `translate(${margin.left}, 0)`)
                .call(
                    d3.axisLeft(y)
                        .ticks(5)
                        .tickFormat(d3.format('d'))
                );

            const line = d3
                .line()
                .x((point, index) => x(monthLabels[index]))
                .y((point) => y(point.value))
                .curve(d3.curveMonotoneX);

            trendData.series.forEach((series) => {
                svg
                    .append('path')
                    .datum(series.values)
                    .attr('class', 'disease-trend-line')
                    .attr('stroke', series.color)
                    .attr('d', line);

                svg
                    .append('g')
                    .selectAll('circle')
                    .data(series.values)
                    .join('circle')
                    .attr('class', 'disease-trend-point')
                    .attr('cx', (point, index) => x(monthLabels[index]))
                    .attr('cy', (point) => y(point.value))
                    .attr('r', 4)
                    .attr('fill', series.color);
            });
        };

        let resizeFrame = 0;

        window.addEventListener('resize', () => {
            window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(renderChart);
        }, { passive: true });

        renderChart();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootDiseaseTrendChart, { once: true });
    } else {
        bootDiseaseTrendChart();
    }
})();
</script>
