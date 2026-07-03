<?php

function getAllPatients(
    mysqli $conn,
    string $searchTerm = ''
): array
{
    $searchTerm = normalizeTextInput($searchTerm);

    if ($searchTerm === '') {
        $sql = "
            SELECT
                patient_id,
                patient_name,
                age,
                gender,
                COALESCE(disease, '') AS disease
            FROM patients
            ORDER BY patient_name, patient_id
        ";

        $result = mysqli_query($conn, $sql);

        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    $stmt = mysqli_prepare(
        $conn,
        "
            SELECT
                patient_id,
                patient_name,
                age,
                gender,
                COALESCE(disease, '') AS disease
            FROM patients
            WHERE
                patient_name LIKE ?
                OR CAST(patient_id AS CHAR) LIKE ?
                OR CAST(age AS CHAR) LIKE ?
                OR gender LIKE ?
                OR COALESCE(disease, '') LIKE ?
            ORDER BY patient_name, patient_id
        "
    );

    if (!$stmt) {
        return [];
    }

    $pattern = '%' . $searchTerm . '%';

    mysqli_stmt_bind_param(
        $stmt,
        'sssss',
        $pattern,
        $pattern,
        $pattern,
        $pattern,
        $pattern
    );
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        return [];
    }

    $patients = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return $patients;
}

function getPatientById(
    mysqli $conn,
    int $patientId
): ?array {
    $stmt = mysqli_prepare(
        $conn,
        "
            SELECT
                patient_id,
                patient_name,
                age,
                gender,
                COALESCE(disease, '') AS disease
            FROM patients
            WHERE patient_id = ?
            LIMIT 1
        "
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $patientId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $patient = mysqli_fetch_assoc($result) ?: null;

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return $patient;
}

function collectPatientPayload(): array
{
    return [
        'patient_name' => normalizeTextInput($_POST['patient_name'] ?? ''),
        'age' => trim((string)($_POST['age'] ?? '')),
        'gender' => trim((string)($_POST['gender'] ?? '')),
        'disease' => normalizeTextInput($_POST['disease'] ?? ''),
    ];
}

function getPatientSearchRedirectParams(): array
{
    $searchTerm = normalizeTextInput($_POST['search'] ?? '');

    return $searchTerm !== ''
        ? ['search' => $searchTerm]
        : [];
}

function validatePatientPayload(array $payload): ?string
{
    $allowedGenders = ['Male', 'Female', 'Other'];

    if ($payload['patient_name'] === '') {
        return 'Patient name is required.';
    }

    if (strlen($payload['patient_name']) > 100) {
        return 'Patient name must be 100 characters or fewer.';
    }

    if (
        $payload['age'] === ''
        || filter_var(
            $payload['age'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 150]]
        ) === false
    ) {
        return 'Age must be a valid whole number between 0 and 150.';
    }

    if (!in_array($payload['gender'], $allowedGenders, true)) {
        return 'Select a valid gender.';
    }

    if (
        $payload['disease'] !== ''
        && strlen($payload['disease']) > 150
    ) {
        return 'Disease must be 150 characters or fewer.';
    }

    return null;
}

function createPatient(mysqli $conn): void
{
    $payload = collectPatientPayload();
    $redirectParams = getPatientSearchRedirectParams();
    $error = validatePatientPayload($payload);

    if ($error !== null) {
        setSessionFormData('patient_form_data', $payload);
        setFlashMessage('error', $error, 'patient_flash');
        redirectToPage(
            'patients',
            array_merge($redirectParams, ['add' => 'new'])
        );
    }

    $age = (int)$payload['age'];

    $stmt = mysqli_prepare(
        $conn,
        "
            INSERT INTO patients (
                patient_name,
                age,
                gender,
                disease
            )
            VALUES (?, ?, ?, ?)
        "
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the patient record.', 'patient_flash');
        redirectToPage(
            'patients',
            array_merge($redirectParams, ['add' => 'new'])
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        'siss',
        $payload['patient_name'],
        $age,
        $payload['gender'],
        $payload['disease']
    );

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        mysqli_stmt_close($stmt);
        setSessionFormData('patient_form_data', $payload);
        setFlashMessage('error', 'Unable to save the patient right now.', 'patient_flash');
        redirectToPage(
            'patients',
            array_merge($redirectParams, ['add' => 'new'])
        );
    }

    mysqli_stmt_close($stmt);

    setFlashMessage('success', 'Patient added successfully.', 'patient_flash');
    redirectToPage('patients', $redirectParams);
}

function updatePatient(mysqli $conn): void
{
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $redirectParams = getPatientSearchRedirectParams();
    $existingPatient = getPatientById($conn, $patientId);

    if ($patientId <= 0 || $existingPatient === null) {
        setFlashMessage('error', 'The selected patient could not be found.', 'patient_flash');
        redirectToPage('patients', $redirectParams);
    }

    $payload = collectPatientPayload();
    $error = validatePatientPayload($payload);

    if ($error !== null) {
        setSessionFormData('patient_form_data', $payload);
        setFlashMessage('error', $error, 'patient_flash');
        redirectToPage(
            'patients',
            array_merge($redirectParams, ['edit' => $patientId])
        );
    }

    $age = (int)$payload['age'];

    $stmt = mysqli_prepare(
        $conn,
        "
            UPDATE patients
            SET
                patient_name = ?,
                age = ?,
                gender = ?,
                disease = ?
            WHERE patient_id = ?
        "
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the patient update.', 'patient_flash');
        redirectToPage(
            'patients',
            array_merge($redirectParams, ['edit' => $patientId])
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sissi',
        $payload['patient_name'],
        $age,
        $payload['gender'],
        $payload['disease'],
        $patientId
    );

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        mysqli_stmt_close($stmt);
        setSessionFormData('patient_form_data', $payload);
        setFlashMessage('error', 'Unable to update the patient right now.', 'patient_flash');
        redirectToPage(
            'patients',
            array_merge($redirectParams, ['edit' => $patientId])
        );
    }

    mysqli_stmt_close($stmt);

    setFlashMessage('success', 'Patient updated successfully.', 'patient_flash');
    redirectToPage('patients', $redirectParams);
}

function deletePatient(mysqli $conn): void
{
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $redirectParams = getPatientSearchRedirectParams();
    $patient = getPatientById($conn, $patientId);

    if ($patientId <= 0 || $patient === null) {
        setFlashMessage('error', 'The selected patient could not be found.', 'patient_flash');
        redirectToPage('patients', $redirectParams);
    }

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM patients WHERE patient_id = ?"
    );

    if (!$stmt) {
        setFlashMessage('error', 'Unable to prepare the patient delete action.', 'patient_flash');
        redirectToPage('patients', $redirectParams);
    }

    mysqli_stmt_bind_param($stmt, 'i', $patientId);

    $execution = executeStatementSafely($stmt);

    if (!$execution['success']) {
        mysqli_stmt_close($stmt);
        setFlashMessage('error', 'Unable to delete the patient right now.', 'patient_flash');
        redirectToPage('patients', $redirectParams);
    }

    mysqli_stmt_close($stmt);

    setFlashMessage('success', 'Patient deleted successfully.', 'patient_flash');
    redirectToPage('patients', $redirectParams);
}

function handlePatientActions(mysqli $conn): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (isset($_POST['create_patient'])) {
        createPatient($conn);
        return;
    }

    if (isset($_POST['update_patient'])) {
        updatePatient($conn);
        return;
    }

    if (isset($_POST['delete_patient'])) {
        deletePatient($conn);
        return;
    }
}
