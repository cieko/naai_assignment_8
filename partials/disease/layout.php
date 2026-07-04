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
    <?php include __DIR__ . '/disease-main/header.php'; ?>

    <?php include __DIR__ . '/disease-main/summary.php'; ?>

    <?php include __DIR__ . '/disease-main/analytics-grid.php'; ?>

    <?php include __DIR__ . '/disease-main/department-breakdown.php'; ?>

    <?php include __DIR__ . '/disease-main/recent-cases.php'; ?>
</section>

<?php include __DIR__ . '/disease-main/chart-script.php'; ?>
