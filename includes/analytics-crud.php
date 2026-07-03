<?php

function getCriticalDepartments(mysqli $conn): array
{
    $sql = "
        SELECT
            d.department_name,
            COUNT(a.admission_id) AS patient_count
        FROM departments d
        INNER JOIN doctors doc
            ON d.department_id = doc.department_id
        INNER JOIN admissions a
            ON doc.doctor_id = a.doctor_id
        WHERE a.status = 'Admitted'
        GROUP BY
            d.department_id,
            d.department_name
        ORDER BY patient_count DESC
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die(mysqli_error($conn));
    }

    $departments = mysqli_fetch_all($result, MYSQLI_ASSOC);

    $totalPatients = array_sum(
        array_column($departments, 'patient_count')
    );

    foreach ($departments as &$department) {

        $department["percentage"] =
            $totalPatients > 0
            ? round(
                ($department["patient_count"] / $totalPatients) * 100
            )
            : 0;
    }

    unset($department);

    return $departments;
}

// function getPatientsByAge(mysqli $conn): array
// {
//     $sql = "SELECT patient_id, age FROM patients";

//     $result = mysqli_query($conn, $sql);

//     $palette = [
//         '#68D391',
//         '#81E6D9',
//         '#90CDF4',
//         '#F6AD55',
//         '#F687B3',
//         '#B794F4'
//     ];

//     $patients = [];

//     while ($row = mysqli_fetch_assoc($result)) {

//         $age = (int)$row['age'];

//         $patients[] = [
//             'id' => $row['patient_id'],
//             'age' => $age,
//             'value' => 1,
//             'color' => $palette[$age % count($palette)]
//         ];
//     }

//     return $patients;
// }

// function getPatientsByGender(mysqli $conn): array
// {
//     $sql = "SELECT gender,COUNT(*) total FROM patients GROUP BY gender";

//     $result = mysqli_query($conn, $sql);

//     $gender = [
//         'Male' => 0,
//         'Female' => 0,
//         'Other' => 0
//     ];

//     while ($row = mysqli_fetch_assoc($result)) {
//         $gender[$row['gender']] = $row['total'];
//     }

//     return $gender;
// }

function getAllStaff(mysqli $conn): array
{
    $sql = "
        SELECT
            d.doctor_id,
            d.doctor_name,
            d.department_id,
            dep.department_name,
            COALESCE(d.specialization, '') AS specialization,
            COALESCE(d.phone, '') AS phone
        FROM doctors d
        INNER JOIN departments dep
            ON d.department_id = dep.department_id
        ORDER BY d.doctor_name
    ";

    $result = mysqli_query($conn, $sql);

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function normalizeStaffReturn(?string $return): string
{
    $allowedReturns = ['staff', 'new', 'edit'];

    return in_array($return, $allowedReturns, true)
        ? $return
        : 'staff';
}

function resolveStaffReturnParams(
    string $return,
    ?int $staffId = null
): array {
    if ($return === 'new') {
        return ['add' => 'new'];
    }

    if ($return === 'edit' && $staffId !== null && $staffId > 0) {
        return ['edit' => $staffId];
    }

    return [];
}

function getDepartmentModalParams(
    string $return,
    ?int $staffId = null
): array {
    $params = [
        'add' => 'department',
        'return' => normalizeStaffReturn($return),
    ];

    if ($return === 'edit' && $staffId !== null && $staffId > 0) {
        $params['staff_id'] = $staffId;
    }

    return $params;
}

function getAllDepartments(mysqli $conn): array
{
    $sql = "
        SELECT
            dep.department_id,
            dep.department_name,
            COUNT(doc.doctor_id) AS doctor_count
        FROM departments dep
        LEFT JOIN doctors doc
            ON dep.department_id = doc.department_id
        GROUP BY
            dep.department_id,
            dep.department_name
        ORDER BY dep.department_name
    ";

    $result = mysqli_query($conn, $sql);

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getStaffMemberById(
    mysqli $conn,
    int $doctorId
): ?array {
    $stmt = mysqli_prepare(
        $conn,
        "
            SELECT
                d.doctor_id,
                d.doctor_name,
                d.department_id,
                dep.department_name,
                COALESCE(d.specialization, '') AS specialization,
                COALESCE(d.phone, '') AS phone
            FROM doctors d
            INNER JOIN departments dep
                ON d.department_id = dep.department_id
            WHERE d.doctor_id = ?
            LIMIT 1
        "
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $doctorId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $staffMember = mysqli_fetch_assoc($result) ?: null;

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return $staffMember;
}

function getDepartmentById(
    mysqli $conn,
    int $departmentId
): ?array {
    $stmt = mysqli_prepare(
        $conn,
        "
            SELECT
                dep.department_id,
                dep.department_name,
                COUNT(doc.doctor_id) AS doctor_count
            FROM departments dep
            LEFT JOIN doctors doc
                ON dep.department_id = doc.department_id
            WHERE dep.department_id = ?
            GROUP BY
                dep.department_id,
                dep.department_name
            LIMIT 1
        "
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $departmentId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $department = mysqli_fetch_assoc($result) ?: null;

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return $department;
}

function departmentExists(
    mysqli $conn,
    int $departmentId
): bool {
    return getDepartmentById($conn, $departmentId) !== null;
}

function collectStaffPayload(): array
{
    return [
        'doctor_name' => normalizeTextInput($_POST['doctor_name'] ?? ''),
        'department_id' => (int)($_POST['department_id'] ?? 0),
        'specialization' => normalizeTextInput($_POST['specialization'] ?? ''),
        'phone' => normalizeTextInput($_POST['phone'] ?? ''),
    ];
}

function validateStaffPayload(
    mysqli $conn,
    array $payload
): ?string {
    if ($payload['doctor_name'] === '') {
        return 'Doctor name is required.';
    }

    if (strlen($payload['doctor_name']) > 100) {
        return 'Doctor name must be 100 characters or fewer.';
    }

    if (
        $payload['department_id'] <= 0
        || !departmentExists($conn, $payload['department_id'])
    ) {
        return 'Select a valid department.';
    }

    if (
        $payload['specialization'] !== ''
        && strlen($payload['specialization']) > 100
    ) {
        return 'Specialization must be 100 characters or fewer.';
    }

    if (
        $payload['phone'] !== ''
        && strlen($payload['phone']) > 20
    ) {
        return 'Phone must be 20 characters or fewer.';
    }

    return null;
}

function createStaff(mysqli $conn): void
{
    $payload = collectStaffPayload();

    $error = validateStaffPayload($conn, $payload);

    if ($error !== null) {
        setSessionFormData('staff_form_data', $payload);
        setFlashMessage('error', $error, 'staff_flash');
        redirectToPage('staff', ['add' => 'new']);
    }

    $stmt = mysqli_prepare(
        $conn,
        "
            INSERT INTO doctors (
                doctor_name,
                department_id,
                specialization,
                phone
            )
            VALUES (?, ?, ?, ?)
        "
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the staff record.', 'staff_flash');
        redirectToPage('staff', ['add' => 'new']);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'siss',
        $payload['doctor_name'],
        $payload['department_id'],
        $payload['specialization'],
        $payload['phone']
    );

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        mysqli_stmt_close($stmt);
        setSessionFormData('staff_form_data', $payload);
        setFlashMessage('error', 'Unable to save the staff member right now.', 'staff_flash');
        redirectToPage('staff', ['add' => 'new']);
    }

    mysqli_stmt_close($stmt);

    setFlashMessage('success', 'Staff member added successfully.', 'staff_flash');
    redirectToPage('staff');
}

function updateStaff(mysqli $conn): void
{
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $existingStaff = getStaffMemberById($conn, $doctorId);

    if ($doctorId <= 0 || $existingStaff === null) {
        setFlashMessage('error', 'The selected staff member could not be found.', 'staff_flash');
        redirectToPage('staff');
    }

    $payload = collectStaffPayload();
    $error = validateStaffPayload($conn, $payload);

    if ($error !== null) {
        setSessionFormData('staff_form_data', $payload);
        setFlashMessage('error', $error, 'staff_flash');
        redirectToPage('staff', ['edit' => $doctorId]);
    }

    $stmt = mysqli_prepare(
        $conn,
        "
            UPDATE doctors
            SET
                doctor_name = ?,
                department_id = ?,
                specialization = ?,
                phone = ?
            WHERE doctor_id = ?
        "
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the staff update.', 'staff_flash');
        redirectToPage('staff', ['edit' => $doctorId]);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sissi',
        $payload['doctor_name'],
        $payload['department_id'],
        $payload['specialization'],
        $payload['phone'],
        $doctorId
    );

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        mysqli_stmt_close($stmt);
        setSessionFormData('staff_form_data', $payload);
        setFlashMessage('error', 'Unable to update the staff member right now.', 'staff_flash');
        redirectToPage('staff', ['edit' => $doctorId]);
    }

    mysqli_stmt_close($stmt);

    setFlashMessage('success', 'Staff member updated successfully.', 'staff_flash');
    redirectToPage('staff');
}

function doctorHasDependencies(
    mysqli $conn,
    int $doctorId
): bool {
    $stmt = mysqli_prepare(
        $conn,
        "
            SELECT
                EXISTS(
                    SELECT 1
                    FROM admissions
                    WHERE doctor_id = ?
                ) AS has_admissions,
                EXISTS(
                    SELECT 1
                    FROM visit_history
                    WHERE doctor_id = ?
                ) AS has_visit_history
        "
    );

    if (!$stmt) {
        return true;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $doctorId, $doctorId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        return true;
    }

    $row = mysqli_fetch_assoc($result) ?: [];

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return !empty($row['has_admissions']) || !empty($row['has_visit_history']);
}

function deleteStaff(mysqli $conn): void
{
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $staffMember = getStaffMemberById($conn, $doctorId);

    if ($doctorId <= 0 || $staffMember === null) {
        setFlashMessage('error', 'The selected staff member could not be found.', 'staff_flash');
        redirectToPage('staff');
    }

    if (doctorHasDependencies($conn, $doctorId)) {
        setFlashMessage(
            'error',
            'This staff member cannot be deleted because related admissions or visit history records already exist.',
            'staff_flash'
        );
        redirectToPage('staff');
    }

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM doctors WHERE doctor_id = ?"
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the delete action.', 'staff_flash');
        redirectToPage('staff');
    }

    mysqli_stmt_bind_param($stmt, 'i', $doctorId);

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        mysqli_stmt_close($stmt);
        setFlashMessage('error', 'Unable to delete the staff member right now.', 'staff_flash');
        redirectToPage('staff');
    }

    mysqli_stmt_close($stmt);

    setFlashMessage('success', 'Staff member deleted successfully.', 'staff_flash');
    redirectToPage('staff');
}

function createDepartment(mysqli $conn): void
{
    $return = normalizeStaffReturn(
        $_POST['return'] ?? $_GET['return'] ?? 'staff'
    );
    $returnStaffId = (int)($_POST['return_staff_id'] ?? $_GET['staff_id'] ?? 0);
    $departmentName = normalizeTextInput($_POST['department_name'] ?? '');
    $modalParams = getDepartmentModalParams($return, $returnStaffId);

    if ($departmentName === '') {
        setSessionFormData('department_form_data', [
            'department_name' => $departmentName,
        ]);
        setFlashMessage('error', 'Department name is required.', 'staff_flash');
        redirectToPage('staff', $modalParams);
    }

    if (strlen($departmentName) > 100) {
        setSessionFormData('department_form_data', [
            'department_name' => $departmentName,
        ]);
        setFlashMessage('error', 'Department name must be 100 characters or fewer.', 'staff_flash');
        redirectToPage('staff', $modalParams);
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO departments (department_name) VALUES (?)"
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the department record.', 'staff_flash');
        redirectToPage('staff', $modalParams);
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $departmentName
    );

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        $errorCode = $execution['error_code'];

        mysqli_stmt_close($stmt);

        setSessionFormData('department_form_data', [
            'department_name' => $departmentName,
        ]);

        if ($errorCode === 1062) {
            setFlashMessage('error', 'That department already exists.', 'staff_flash');
        } else {
            setFlashMessage('error', 'Unable to save the department right now.', 'staff_flash');
        }

        redirectToPage('staff', $modalParams);
    }

    $departmentId = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    $redirectParams = resolveStaffReturnParams($return, $returnStaffId);

    if (!empty($redirectParams)) {
        $redirectParams['selected_department'] = $departmentId;
    }

    setFlashMessage('success', 'Department added successfully.', 'staff_flash');
    redirectToPage('staff', $redirectParams);
}

function deleteDepartment(mysqli $conn): void
{
    $return = normalizeStaffReturn($_POST['return'] ?? 'staff');
    $returnStaffId = (int)($_POST['return_staff_id'] ?? 0);
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $modalParams = getDepartmentModalParams($return, $returnStaffId);

    if ($departmentId <= 0) {
        setFlashMessage('error', 'Select a valid department to delete.', 'staff_flash');
        redirectToPage('staff', $modalParams);
    }

    $department = getDepartmentById($conn, $departmentId);

    if ($department === null) {
        setFlashMessage('error', 'The selected department could not be found.', 'staff_flash');
        redirectToPage('staff', $modalParams);
    }

    if ((int)$department['doctor_count'] > 0) {
        setFlashMessage(
            'error',
            'This department cannot be deleted while staff members are still assigned to it.',
            'staff_flash'
        );
        redirectToPage('staff', $modalParams);
    }

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM departments WHERE department_id = ?"
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the department delete action.', 'staff_flash');
        redirectToPage('staff', $modalParams);
    }

    mysqli_stmt_bind_param($stmt, 'i', $departmentId);

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        mysqli_stmt_close($stmt);
        setFlashMessage('error', 'Unable to delete the department right now.', 'staff_flash');
        redirectToPage('staff', $modalParams);
    }

    mysqli_stmt_close($stmt);

    setFlashMessage('success', 'Department deleted successfully.', 'staff_flash');
    redirectToPage('staff', $modalParams);
}

function handleStaffActions(mysqli $conn): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (isset($_POST['create_department'])) {
        createDepartment($conn);
        return;
    }

    if (isset($_POST['delete_department'])) {
        deleteDepartment($conn);
        return;
    }

    if (isset($_POST['create_staff'])) {
        createStaff($conn);
        return;
    }

    if (isset($_POST['update_staff'])) {
        updateStaff($conn);
        return;
    }

    if (isset($_POST['delete_staff'])) {
        deleteStaff($conn);
        return;
    }
}
