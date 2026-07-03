<?php

/** @var mysqli $conn */

handleStaffActions($conn);

$staffFormData = pullSessionFormData('staff_form_data');
$departmentFormData = pullSessionFormData('department_form_data');
$flash = pullFlashMessage('staff_flash');

$staff = getAllStaff($conn);
$departments = getAllDepartments($conn);

$showAddStaffModal = (($_GET['add'] ?? '') === 'new');
$showAddDepartmentModal = (($_GET['add'] ?? '') === 'department');

$editStaffId = (int)($_GET['edit'] ?? 0);
$deleteStaffId = (int)($_GET['delete_staff'] ?? 0);
$deleteDepartmentId = (int)($_GET['delete_department'] ?? 0);

$editingStaff = $editStaffId > 0
    ? getStaffMemberById($conn, $editStaffId)
    : null;
$deletingStaff = $deleteStaffId > 0
    ? getStaffMemberById($conn, $deleteStaffId)
    : null;

$showEditStaffModal = $editStaffId > 0 && $editingStaff !== null;
$showStaffFormModal = $showAddStaffModal || $showEditStaffModal;
$showDeleteStaffModal = $deleteStaffId > 0 && $deletingStaff !== null;

$return = normalizeStaffReturn(
    $_GET['return']
        ?? ($showEditStaffModal
            ? 'edit'
            : ($showAddStaffModal ? 'new' : 'staff'))
);

$returnStaffId = (int)($_GET['staff_id'] ?? 0);
$selectedDepartmentId = (int)($_GET['selected_department'] ?? 0);
$deletingDepartment = $deleteDepartmentId > 0
    ? getDepartmentById($conn, $deleteDepartmentId)
    : null;
$showDeleteDepartmentModal = (
    $showAddDepartmentModal
    && $deleteDepartmentId > 0
    && $deletingDepartment !== null
);

if ($editStaffId > 0 && $editingStaff === null) {
    $flash ??= [
        'type' => 'error',
        'message' => 'The selected staff member could not be found.',
    ];
}

if ($deleteStaffId > 0 && $deletingStaff === null) {
    $flash ??= [
        'type' => 'error',
        'message' => 'The selected staff member could not be found.',
    ];
}

if (
    $showAddDepartmentModal
    && $deleteDepartmentId > 0
    && $deletingDepartment === null
) {
    $flash ??= [
        'type' => 'error',
        'message' => 'The selected department could not be found.',
    ];
}

$staffFormValues = [
    'doctor_name' => $staffFormData['doctor_name']
        ?? ($editingStaff['doctor_name'] ?? ''),
    'department_id' => (int)($staffFormData['department_id']
        ?? ($selectedDepartmentId > 0
            ? $selectedDepartmentId
            : ($editingStaff['department_id'] ?? 0))),
    'specialization' => $staffFormData['specialization']
        ?? ($editingStaff['specialization'] ?? ''),
    'phone' => $staffFormData['phone']
        ?? ($editingStaff['phone'] ?? ''),
];

$departmentFormValues = [
    'department_name' => $departmentFormData['department_name'] ?? '',
];

$staffModalTitle = $showEditStaffModal
    ? 'Edit Staff'
    : 'Add New Staff';

$staffSubmitAction = $showEditStaffModal
    ? 'update_staff'
    : 'create_staff';

$staffSubmitLabel = $showEditStaffModal
    ? 'Update Staff'
    : 'Save Staff';

$departmentOrigin = $showEditStaffModal ? 'edit' : 'new';
$departmentOriginStaffId = $showEditStaffModal ? $editStaffId : null;

$departmentModalUrl = pageUrl(
    getDepartmentModalParams(
        $departmentOrigin,
        $departmentOriginStaffId
    ),
    'staff'
);

$departmentCloseUrl = pageUrl(
    resolveStaffReturnParams($return, $returnStaffId),
    'staff'
);
$departmentModalBaseUrl = pageUrl(
    getDepartmentModalParams($return, $returnStaffId),
    'staff'
);

$escape = static function (?string $value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<?php include __DIR__ . '/../flash.php'; ?>

<?php if ($showStaffFormModal): ?>

    <div class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><?= $escape($staffModalTitle) ?></h3>

                <a
                    href="<?= $escape(pageUrl([], 'staff')) ?>"
                    class="modal-close">
                    <i class="ri-close-line"></i>
                </a>
            </div>

            <form
                action=""
                method="post"
                class="modal-form">

                <?php if ($showEditStaffModal): ?>

                    <input
                        type="hidden"
                        name="doctor_id"
                        value="<?= (int)$editStaffId ?>">

                <?php endif; ?>

                <div class="form-group">
                    <label for="doctor_name">
                        Doctor Name
                    </label>

                    <input
                        id="doctor_name"
                        name="doctor_name"
                        type="text"
                        value="<?= $escape($staffFormValues['doctor_name']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="department">
                        Department
                    </label>

                    <div class="inline-field">
                        <select
                            id="department"
                            name="department_id"
                            required>

                            <option value="">
                                Select Department
                            </option>

                            <?php foreach ($departments as $department): ?>

                                <?php
                                $departmentId = (int)$department['department_id'];
                                $isSelected = $departmentId === (int)$staffFormValues['department_id'];
                                ?>

                                <option
                                    value="<?= $departmentId ?>"
                                    <?= $isSelected ? 'selected' : '' ?>>
                                    <?= $escape($department['department_name']) ?>
                                </option>

                            <?php endforeach; ?>
                        </select>

                        <a
                            href="<?= $escape($departmentModalUrl) ?>"
                            class="add-department-link">
                            <i class="ri-add-circle-line"></i>
                            Add Department
                        </a>
                    </div>

                    <?php if (empty($departments)): ?>

                        <p class="field-note">
                            Create at least one department before saving staff.
                        </p>

                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="specialization">
                        Specialization
                    </label>

                    <input
                        id="specialization"
                        name="specialization"
                        type="text"
                        value="<?= $escape($staffFormValues['specialization']) ?>">
                </div>

                <div class="form-group">
                    <label for="phone">
                        Phone
                    </label>

                    <input
                        id="phone"
                        name="phone"
                        type="text"
                        value="<?= $escape($staffFormValues['phone']) ?>">
                </div>

                <div class="modal-footer">
                    <a
                        href="<?= $escape(pageUrl([], 'staff')) ?>"
                        class="secondary-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="<?= $escape($staffSubmitAction) ?>"
                        class="primary-btn">
                        <?= $escape($staffSubmitLabel) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php if ($showDeleteStaffModal): ?>

    <div class="modal-backdrop modal-backdrop--stacked">
        <div class="modal modal--confirm">
            <div class="modal-header">
                <h3>Delete Staff</h3>

                <a
                    href="<?= $escape(pageUrl([], 'staff')) ?>"
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
                        <strong><?= $escape($deletingStaff['doctor_name']) ?></strong>
                        from the staff list?
                    </p>
                    <span>This action cannot be undone.</span>
                </div>

                <input
                    type="hidden"
                    name="doctor_id"
                    value="<?= $deleteStaffId ?>">

                <div class="modal-footer">
                    <a
                        href="<?= $escape(pageUrl([], 'staff')) ?>"
                        class="secondary-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="delete_staff"
                        class="danger-btn">
                        Delete Staff
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php if ($showAddDepartmentModal): ?>

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

<?php endif; ?>

<?php if ($showDeleteDepartmentModal): ?>

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

<?php endif; ?>

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
