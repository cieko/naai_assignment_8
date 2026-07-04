<?php
/** @var int $deleteDepartmentId */
/** @var array<string, mixed> $deletingDepartment */
/** @var string $departmentModalBaseUrl */
/** @var Closure $escape */
/** @var string $return */
/** @var int $returnStaffId */
?>

<div class="modal-backdrop modal-backdrop--stacked">
    <div class="modal modal--confirm">
        <div class="modal-header">
            <h3>Delete Department</h3>

            <a
                href="<?= $escape($departmentModalBaseUrl) ?>"
                class="modal-close">
                <i class="ri-close-line"></i>
            </a>
        </div>

        <form
            action=""
            method="post"
            class="modal-form">

            <div class="confirm-copy">
                <p>
                    Delete
                    <strong><?= $escape($deletingDepartment['department_name']) ?></strong>
                    department?
                </p>
                <span>
                    This is only allowed when the department has no assigned staff.
                </span>
            </div>

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

            <input
                type="hidden"
                name="department_id"
                value="<?= $deleteDepartmentId ?>">

            <div class="modal-footer">
                <a
                    href="<?= $escape($departmentModalBaseUrl) ?>"
                    class="secondary-btn">
                    Cancel
                </a>

                <button
                    type="submit"
                    name="delete_department"
                    class="danger-btn">
                    Delete Department
                </button>
            </div>
        </form>
    </div>
</div>
