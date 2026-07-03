<?php

/** @var mysqli $conn */

$departments = getCriticalDepartments($conn);

$colors = [
    "ICU" => "#ff6b81",
    "Cardiology" => "#2ed573",
    "Neurology" => "#1e90ff",
    "Gynecology" => "#a55eea",
    "Orthopedics" => "#fbc531"
];
?>

<section class="card critical-condition">
    <div class="title-section">

        <div class="headline-wrapper">
            <h4>Critical Condition</h4>
            <p>Real-time monitoring for critical patients.</p>
        </div>

        <div class="legends">
            <div class="legends">
                <?php foreach ($departments as $department): ?>

                    <?php
                    $departmentName = $department["department_name"];
                    $color = $colors[$departmentName] ?? "#999999";
                    ?>

                    <div class="legend-item">
                        <span
                            class="legend-color"
                            style="background-color: <?= htmlspecialchars($color) ?>;">
                        </span>

                        <span class="legend-title"><?= htmlspecialchars($departmentName) ?></span>
                    </div>

                <?php endforeach; ?>
            </div>
        </div>

        <div class="actions">
            <i class="ri-install-line"></i>
        </div>

    </div>

    <div class="critical-condition-body">

        <div class="activity-panel">

            <?php foreach ($departments as $department): ?>

                <?php

                $icons = [
                    "ICU" => "ri-hospital-line",
                    "Cardiology" => "ri-heart-pulse-line",
                    "Neurology" => "ri-brain-line",
                    "Gynecology" => "ri-women-line",
                    "Orthopedics" => "ri-wheelchair-line"
                ];

                $color = $colors[$department["department_name"]] ?? "#999";
                $icon = $icons[$department["department_name"]] ?? "ri-hospital-line";

                ?>

                <div class="activity">

                    <div class="activity-info">

                        <i
                            class="<?= $icon ?>"
                            style="color: <?= htmlspecialchars($color) ?>;">
                        </i>

                        <span>
                            <?= $department["patient_count"] ?>
                            <?= htmlspecialchars($department["department_name"]) ?>
                            Patient<?= $department["patient_count"] > 1 ? "s" : "" ?>
                        </span>

                    </div>

                    <div class="activity-line"></div>

                </div>
            <?php endforeach; ?>
        </div>

        <div class="chart">

            <?php foreach ($departments as $department): ?>

                <?php
                $color = $colors[$department["department_name"]] ?? "#999";
                ?>

                <div class="chart-bar">

                    <h3><?= $department["percentage"] ?>%</h3>

                    <div
                        class="chart-fill"
                        style="height: <?= $department["percentage"] ?>%;--color: <?= $color ?>;">
                    </div>

                    <div class="chart-badge" style="background:<?= $color ?>; ?>;">
                        <?= $department["patient_count"] ?> Patients
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>