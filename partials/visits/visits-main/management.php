<?php
/** @var Closure $escape */
/** @var array<int, array<string, mixed>> $visits */
?>

<section class="staff">
    <div class="staff-header">
        <div class="headline">
            <h2>Visit Management</h2>
            <p>Track admissions, consultations, discharge details, and billing records.</p>
        </div>

        <a
            href="<?= $escape(pageUrl(['add' => 'new'], 'visits')) ?>"
            class="staff-add-btn">
            Add New Visit
        </a>
    </div>

    <div class="staff-card">
        <table class="staff-table staff-table--wide">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Department</th>
                    <th>Admission Date</th>
                    <th>Discharge Date</th>
                    <th>Treatment Cost</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($visits)): ?>

                    <tr>
                        <td
                            colspan="8"
                            class="staff-empty">
                            No visit records have been added yet.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($visits as $visit): ?>

                        <tr>
                            <td><?= $escape($visit['patient_name']) ?></td>
                            <td><?= $escape($visit['doctor_name']) ?></td>
                            <td><?= $escape($visit['department_name']) ?></td>
                            <td><?= $escape($visit['admission_date']) ?></td>
                            <td><?= $escape($visit['discharge_date'] ?: 'Admitted') ?></td>
                            <td>Rs. <?= $escape(number_format((float)$visit['amount'], 2)) ?></td>
                            <td><?= $escape($visit['payment_status']) ?></td>

                            <td>
                                <div class="staff-actions">
                                    <a
                                        href="<?= $escape(pageUrl(['edit' => (int)$visit['admission_id']], 'visits')) ?>"
                                        class="staff-action-btn staff-edit"
                                        aria-label="Edit visit for <?= $escape($visit['patient_name']) ?>">
                                        <i class="ri-edit-box-line"></i>
                                    </a>

                                    <a
                                        href="<?= $escape(pageUrl(['delete_visit' => (int)$visit['admission_id']], 'visits')) ?>"
                                        class="staff-action-btn staff-delete"
                                        aria-label="Delete visit for <?= $escape($visit['patient_name']) ?>">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>
        </table>
    </div>
</section>
