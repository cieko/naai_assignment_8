<?php
/** @var Closure $escape */
/** @var Closure $formatDate */
/** @var array<string, string> $paymentBadgeMap */
/** @var array<int, array<string, mixed>> $recentCases */
/** @var array<string, string> $statusBadgeMap */
?>

<section
    class="staff-card disease-table-card"
    aria-labelledby="disease-cases-heading"
    tabindex="0">
    <div class="disease-table-header">
        <div class="headline">
            <h3 id="disease-cases-heading">Recent Disease Cases</h3>
            <p>Latest admitted or discharged cases with doctor, department, treatment cost, and payment status.</p>
        </div>
    </div>

    <?php if (empty($recentCases)): ?>

        <div class="staff-empty">
            No disease-linked admission records are available yet.
        </div>

    <?php else: ?>

        <table class="staff-table staff-table--wide">
            <caption class="sr-only">
                Recent disease-linked admission records with doctor, department, billing, payment, and admission status.
            </caption>

            <thead>
                <tr>
                    <th scope="col">Patient</th>
                    <th scope="col">Disease</th>
                    <th scope="col">Doctor</th>
                    <th scope="col">Department</th>
                    <th scope="col">Admission</th>
                    <th scope="col">Discharge</th>
                    <th scope="col">Treatment Cost</th>
                    <th scope="col">Payment</th>
                    <th scope="col">Status</th>
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
                        <th scope="row"><?= $escape($case['patient_name']) ?></th>
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
