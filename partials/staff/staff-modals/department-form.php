<?php
/** @var string $departmentCloseUrl */
/** @var array<string, string> $departmentFormValues */
/** @var array<int, array<string, mixed>> $departments */
/** @var Closure $escape */
/** @var string $return */
/** @var int $returnStaffId */
?>

<div class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Department</h3>

            <a
                href="<?= $escape($departmentCloseUrl) ?>"
                class="modal-close">
                <i class="ri-close-line"></i>
            </a>
        </div>

        <form
            action=""
            method="post"
            class="modal-form modal-form--compact">

            <input
                type="hidden"
                name="return"
                value="<?= $escape($return) ?>">

            <?php if ($return === 'edit' && $returnStaffId > 0): ?>

                <input
                    type="hidden"
                    name="return_staff_id"
                    value="<?= $returnStaffId ?>">

            <?php endif; ?>

            <div class="form-group">
                <label for="department_name">
                    Department Name
                </label>

                <input
                    id="department_name"
                    name="department_name"
                    type="text"
                    placeholder="e.g. Cardiology"
                    autocomplete="off"
                    value="<?= $escape($departmentFormValues['department_name']) ?>"
                    required>
            </div>

            <div class="modal-footer">
                <a
                    href="<?= $escape($departmentCloseUrl) ?>"
                    class="secondary-btn">
                    Cancel
                </a>

                <button
                    type="submit"
                    name="create_department"
                    class="primary-btn">
                    Save Department
                </button>
            </div>
        </form>

        <div class="department-manager">
            <div class="department-manager-header">
                <div>
                    <h4>Manage Departments</h4>
                    <p>
                        Departments can be deleted only when no staff members are assigned to them.
                    </p>
                </div>
            </div>

            <?php if (empty($departments)): ?>

                <p class="department-empty">
                    No departments have been created yet.
                </p>

            <?php else: ?>

                <div class="department-list">

                    <?php foreach ($departments as $department): ?>

                        <?php
                        $departmentId = (int)$department['department_id'];
                        $doctorCount = (int)$department['doctor_count'];
                        $canDelete = $doctorCount === 0;
                        ?>

                        <div class="department-item">
                            <div class="department-item-info">
                                <strong>
                                    <?= $escape($department['department_name']) ?>
                                </strong>

                                <span>
                                    <?= $doctorCount === 0
                                        ? 'No staff assigned'
                                        : $doctorCount . ' staff assigned' ?>
                                </span>
                            </div>

                            <div class="department-item-form">
                                <?php if ($canDelete): ?>

                                    <a
                                        href="<?= $escape(
                                            pageUrl(
                                                array_merge(
                                                    getDepartmentModalParams($return, $returnStaffId),
                                                    ['delete_department' => $departmentId]
                                                ),
                                                'staff'
                                            )
                                        ) ?>"
                                        class="danger-btn">
                                        Delete
                                    </a>

                                <?php else: ?>

                                    <span class="danger-btn danger-btn--muted">
                                        In Use
                                    </span>

                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </div>
    </div>
</div>
