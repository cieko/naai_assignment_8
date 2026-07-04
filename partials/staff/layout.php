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
    <?php include __DIR__ . '/staff-modals/staff-form.php'; ?>

<?php endif; ?>

<?php if ($showDeleteStaffModal): ?>
    <?php include __DIR__ . '/staff-modals/staff-delete.php'; ?>

<?php endif; ?>

<?php if ($showAddDepartmentModal): ?>
    <?php include __DIR__ . '/staff-modals/department-form.php'; ?>

<?php endif; ?>

<?php if ($showDeleteDepartmentModal): ?>
    <?php include __DIR__ . '/staff-modals/department-delete.php'; ?>

<?php endif; ?>

<?php include __DIR__ . '/staff-main/management.php'; ?>
