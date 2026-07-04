<?php

/** @var mysqli $conn */

$operationsOverview = getDashboardOperationsOverview($conn);

$formatCount = static function (int $value): string {
    return htmlspecialchars(
        number_format($value),
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatPercent = static function (int $value): string {
    return htmlspecialchars(
        number_format($value) . '%',
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<div
    class="card appointment-overview"
    aria-labelledby="appointment-overview-heading">

    <h4 id="appointment-overview-heading">Operations Overview</h4>

    <div class="overview-stats">

        <div class="stat">
            <h2><?= $formatCount($operationsOverview['total_visits']) ?></h2>
            <span>Total Visits</span>
        </div>

        <div class="stat">
            <h2><?= $formatCount($operationsOverview['active_admissions']) ?></h2>
            <span>Active Admissions</span>
        </div>

        <div class="stat">
            <h2><?= $formatCount($operationsOverview['paid_bills']) ?></h2>
            <span>Paid Bills</span>
        </div>

        <div class="stat">
            <h2><?= $formatCount($operationsOverview['pending_bills']) ?></h2>
            <span>Pending Bills</span>
        </div>

    </div>

    <div class="overview-details">

        <div class="detail">
            <i class="ri-hotel-bed-line" aria-hidden="true"></i>
            <div class="detail-copy">
                <span>Tracked Beds:</span>
                <strong><?= $formatCount($operationsOverview['tracked_beds']) ?></strong>
            </div>
        </div>

        <div class="detail">
            <i class="ri-hotel-bed-line" aria-hidden="true"></i>
            <div class="detail-copy">
                <span>Occupied Beds:</span>
                <strong><?= $formatCount($operationsOverview['occupied_beds']) ?></strong>
            </div>
        </div>

        <div class="detail">
            <i class="ri-hotel-bed-line" aria-hidden="true"></i>
            <div class="detail-copy">
                <span>Occupancy Rate:</span>
                <strong><?= $formatPercent($operationsOverview['occupancy_rate']) ?></strong>
            </div>
        </div>

    </div>

</div>
