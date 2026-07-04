<?php
/** @var Closure $escape */
/** @var array<int, array<string, mixed>> $visits */
?>

<section
    class="staff"
    aria-labelledby="visits-heading">
    <div class="staff-header">
        <div class="headline">
            <h2 id="visits-heading">Visit Management</h2>
            <p>Track admissions, consultations, discharge details, and billing records.</p>
        </div>

        <a
            href="<?= $escape(pageUrl(['add' => 'new'], 'visits')) ?>"
            class="staff-add-btn">
            Add New Visit
        </a>
    </div>

    <div
        class="staff-card"
        tabindex="0">
        <table class="staff-table staff-table--wide">
            <caption class="sr-only">
                Visit records showing patients, doctors, departments, admission details, payment status, and actions.
            </caption>

            <thead>
                <tr>
                    <th scope="col">Patient</th>
                    <th scope="col">Doctor</th>
                    <th scope="col">Department</th>
                    <th scope="col">Admission Date</th>
                    <th scope="col">Discharge Date</th>
                    <th scope="col">Treatment Cost</th>
                    <th scope="col">Payment Status</th>
                    <th scope="col">Actions</th>
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
                            <th scope="row"><?= $escape($visit['patient_name']) ?></th>
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
                                        <i class="ri-edit-box-line" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="<?= $escape(pageUrl(['delete_visit' => (int)$visit['admission_id']], 'visits')) ?>"
                                        class="staff-action-btn staff-delete"
                                        aria-label="Delete visit for <?= $escape($visit['patient_name']) ?>">
                                        <i class="ri-delete-bin-line" aria-hidden="true"></i>
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
