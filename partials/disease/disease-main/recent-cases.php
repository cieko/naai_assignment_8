<?php
/** @var Closure $escape */
/** @var Closure $formatDate */
/** @var array<string, string> $paymentBadgeMap */
/** @var array<int, array<string, mixed>> $recentCases */
/** @var array<string, string> $statusBadgeMap */
?>

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
