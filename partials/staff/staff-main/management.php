<?php
/** @var Closure $escape */
/** @var array<int, array<string, mixed>> $staff */
?>

<section class="staff">
    <div class="staff-header">
        <div class="headline">
            <h2>Staff Management</h2>
            <p>View and manage all registered doctors across departments.</p>
        </div>

        <a
            href="<?= $escape(pageUrl(['add' => 'new'], 'staff')) ?>"
            class="staff-add-btn">
            Add New Staff
        </a>
    </div>

    <div class="staff-card">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Department</th>
                    <th>Specialization</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($staff)): ?>

                    <tr>
                        <td
                            colspan="5"
                            class="staff-empty">
                            No staff members have been added yet.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($staff as $doctor): ?>

                        <tr>
                            <td><?= $escape($doctor['doctor_name']) ?></td>
                            <td><?= $escape($doctor['department_name']) ?></td>
                            <td><?= $escape($doctor['specialization'] ?: 'Not set') ?></td>
                            <td><?= $escape($doctor['phone'] ?: 'Not set') ?></td>

                            <td>
                                <div class="staff-actions">
                                    <a
                                        href="<?= $escape(pageUrl(['edit' => (int)$doctor['doctor_id']], 'staff')) ?>"
                                        class="staff-action-btn staff-edit"
                                        aria-label="Edit <?= $escape($doctor['doctor_name']) ?>">
                                        <i class="ri-edit-box-line"></i>
                                    </a>

                                    <a
                                        href="<?= $escape(pageUrl(['delete_staff' => (int)$doctor['doctor_id']], 'staff')) ?>"
                                        class="staff-action-btn staff-delete"
                                        aria-label="Delete <?= $escape($doctor['doctor_name']) ?>">
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
