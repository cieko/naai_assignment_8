<?php

function getVisitDoctors(mysqli $conn): array
{
    $sql = "
        SELECT
            d.doctor_id,
            d.doctor_name,
            dep.department_name
        FROM doctors d
        INNER JOIN departments dep
            ON d.department_id = dep.department_id
        ORDER BY d.doctor_name
    ";

    $result = mysqli_query($conn, $sql);

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getVisitRecordsSql(): string
{
    return "
        SELECT
            a.admission_id,
            p.patient_id,
            p.patient_name,
            d.doctor_id,
            d.doctor_name,
            dep.department_name,
            a.admission_date,
            a.discharge_date,
            a.status,
            COALESCE(a.bed_number, '') AS bed_number,
            COALESCE(v.visit_id, 0) AS visit_id,
            COALESCE(v.visit_date, a.admission_date) AS visit_date,
            COALESCE(v.diagnosis, '') AS diagnosis,
            COALESCE(v.notes, '') AS notes,
            COALESCE(b.bill_id, 0) AS bill_id,
            COALESCE(b.amount, 0) AS amount,
            COALESCE(b.payment_status, 'Pending') AS payment_status,
            b.payment_date
        FROM admissions a
        INNER JOIN patients p
            ON a.patient_id = p.patient_id
        INNER JOIN doctors d
            ON a.doctor_id = d.doctor_id
        INNER JOIN departments dep
            ON d.department_id = dep.department_id
        LEFT JOIN (
            SELECT vh.*
            FROM visit_history vh
            INNER JOIN (
                SELECT
                    admission_id,
                    MAX(visit_id) AS visit_id
                FROM visit_history
                WHERE admission_id IS NOT NULL
                GROUP BY admission_id
            ) latest_visit
                ON latest_visit.visit_id = vh.visit_id
        ) v
            ON v.admission_id = a.admission_id
        LEFT JOIN (
            SELECT b1.*
            FROM billing b1
            INNER JOIN (
                SELECT
                    admission_id,
                    MAX(bill_id) AS bill_id
                FROM billing
                GROUP BY admission_id
            ) latest_bill
                ON latest_bill.bill_id = b1.bill_id
        ) b
            ON b.admission_id = a.admission_id
    ";
}

function getAllVisitRecords(mysqli $conn): array
{
    $sql = getVisitRecordsSql() . "
        ORDER BY a.admission_date DESC, a.admission_id DESC
    ";

    $result = mysqli_query($conn, $sql);

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getVisitRecordById(
    mysqli $conn,
    int $admissionId
): ?array {
    $stmt = mysqli_prepare(
        $conn,
        getVisitRecordsSql() . "
            WHERE a.admission_id = ?
            LIMIT 1
        "
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $admissionId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $record = mysqli_fetch_assoc($result) ?: null;

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return $record;
}

function visitPatientExists(
    mysqli $conn,
    int $patientId
): bool {
    return getPatientById($conn, $patientId) !== null;
}

function visitDoctorExists(
    mysqli $conn,
    int $doctorId
): bool {
    return getStaffMemberById($conn, $doctorId) !== null;
}

function collectVisitPayload(): array
{
    return [
        'patient_id' => (int)($_POST['patient_id'] ?? 0),
        'doctor_id' => (int)($_POST['doctor_id'] ?? 0),
        'admission_date' => trim((string)($_POST['admission_date'] ?? '')),
        'discharge_date' => trim((string)($_POST['discharge_date'] ?? '')),
        'bed_number' => normalizeTextInput($_POST['bed_number'] ?? ''),
        'visit_date' => trim((string)($_POST['visit_date'] ?? '')),
        'diagnosis' => normalizeTextInput($_POST['diagnosis'] ?? ''),
        'notes' => trim((string)($_POST['notes'] ?? '')),
        'amount' => trim((string)($_POST['amount'] ?? '')),
        'payment_status' => trim((string)($_POST['payment_status'] ?? 'Pending')),
        'payment_date' => trim((string)($_POST['payment_date'] ?? '')),
    ];
}

function validateVisitPayload(
    mysqli $conn,
    array $payload
): ?string {
    $allowedPaymentStatuses = ['Pending', 'Partial', 'Paid'];

    if (
        $payload['patient_id'] <= 0
        || !visitPatientExists($conn, $payload['patient_id'])
    ) {
        return 'Select a valid patient.';
    }

    if (
        $payload['doctor_id'] <= 0
        || !visitDoctorExists($conn, $payload['doctor_id'])
    ) {
        return 'Select a valid doctor.';
    }

    if (!isValidDateInput($payload['admission_date'])) {
        return 'Admission date is required.';
    }

    if (!isValidDateInput($payload['visit_date'])) {
        return 'Visit date is required.';
    }

    if (
        $payload['discharge_date'] !== ''
        && !isValidDateInput($payload['discharge_date'])
    ) {
        return 'Discharge date must be a valid date.';
    }

    if (
        $payload['payment_date'] !== ''
        && !isValidDateInput($payload['payment_date'])
    ) {
        return 'Payment date must be a valid date.';
    }

    if ($payload['visit_date'] < $payload['admission_date']) {
        return 'Visit date cannot be earlier than the admission date.';
    }

    if (
        $payload['discharge_date'] !== ''
        && $payload['discharge_date'] < $payload['admission_date']
    ) {
        return 'Discharge date cannot be earlier than the admission date.';
    }

    if (
        $payload['bed_number'] !== ''
        && strlen($payload['bed_number']) > 20
    ) {
        return 'Bed number must be 20 characters or fewer.';
    }

    if ($payload['amount'] === '') {
        return 'Treatment cost is required.';
    }

    if (!is_numeric($payload['amount']) || (float)$payload['amount'] < 0) {
        return 'Treatment cost must be a valid positive amount.';
    }

    if (
        !in_array(
            $payload['payment_status'],
            $allowedPaymentStatuses,
            true
        )
    ) {
        return 'Select a valid payment status.';
    }

    return null;
}

function createVisitRecord(mysqli $conn): void
{
    $payload = collectVisitPayload();
    $error = validateVisitPayload($conn, $payload);

    if ($error !== null) {
        setSessionFormData('visit_form_data', $payload);
        setFlashMessage('error', $error, 'visit_flash');
        redirectToPage('visits', ['add' => 'new']);
    }

    $status = $payload['discharge_date'] !== '' ? 'Discharged' : 'Admitted';
    $dischargeDate = $payload['discharge_date'] !== '' ? $payload['discharge_date'] : null;
    $bedNumber = $payload['bed_number'] !== '' ? $payload['bed_number'] : null;
    $diagnosis = $payload['diagnosis'] !== '' ? $payload['diagnosis'] : null;
    $notes = $payload['notes'] !== '' ? $payload['notes'] : null;
    $paymentDate = $payload['payment_date'] !== '' ? $payload['payment_date'] : null;
    $amount = (float)$payload['amount'];

    $transaction = runTransaction(
        $conn,
        static function () use (
            $conn,
            $payload,
            $status,
            $dischargeDate,
            $bedNumber,
            $diagnosis,
            $notes,
            $paymentDate,
            $amount
        ): void {
            $admissionStmt = mysqli_prepare(
                $conn,
                "
                    INSERT INTO admissions (
                        patient_id,
                        doctor_id,
                        admission_date,
                        discharge_date,
                        status,
                        bed_number
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                "
            );

            if (!$admissionStmt) {
                throw new RuntimeException('Unable to prepare admission record.');
            }

            mysqli_stmt_bind_param(
                $admissionStmt,
                'iissss',
                $payload['patient_id'],
                $payload['doctor_id'],
                $payload['admission_date'],
                $dischargeDate,
                $status,
                $bedNumber
            );

            executeStatementOrFail($admissionStmt);

            $admissionId = mysqli_insert_id($conn);

            mysqli_stmt_close($admissionStmt);

            $visitStmt = mysqli_prepare(
                $conn,
                "
                    INSERT INTO visit_history (
                        admission_id,
                        patient_id,
                        doctor_id,
                        visit_date,
                        diagnosis,
                        notes
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                "
            );

            if (!$visitStmt) {
                throw new RuntimeException('Unable to prepare visit history record.');
            }

            mysqli_stmt_bind_param(
                $visitStmt,
                'iiisss',
                $admissionId,
                $payload['patient_id'],
                $payload['doctor_id'],
                $payload['visit_date'],
                $diagnosis,
                $notes
            );

            executeStatementOrFail($visitStmt);
            mysqli_stmt_close($visitStmt);

            $billingStmt = mysqli_prepare(
                $conn,
                "
                    INSERT INTO billing (
                        admission_id,
                        amount,
                        payment_status,
                        payment_date
                    )
                    VALUES (?, ?, ?, ?)
                "
            );

            if (!$billingStmt) {
                throw new RuntimeException('Unable to prepare billing record.');
            }

            mysqli_stmt_bind_param(
                $billingStmt,
                'idss',
                $admissionId,
                $amount,
                $payload['payment_status'],
                $paymentDate
            );

            executeStatementOrFail($billingStmt);
            mysqli_stmt_close($billingStmt);
        }
    );

    if (!$transaction['success']) {
        setSessionFormData('visit_form_data', $payload);
        setFlashMessage('error', 'Unable to save the visit record right now.', 'visit_flash');
        redirectToPage('visits', ['add' => 'new']);
    }

    setFlashMessage('success', 'Visit record added successfully.', 'visit_flash');
    redirectToPage('visits');
}

function updateVisitRecord(mysqli $conn): void
{
    $admissionId = (int)($_POST['admission_id'] ?? 0);
    $existingRecord = getVisitRecordById($conn, $admissionId);

    if ($admissionId <= 0 || $existingRecord === null) {
        setFlashMessage('error', 'The selected visit record could not be found.', 'visit_flash');
        redirectToPage('visits');
    }

    $payload = collectVisitPayload();
    $error = validateVisitPayload($conn, $payload);

    if ($error !== null) {
        setSessionFormData('visit_form_data', $payload);
        setFlashMessage('error', $error, 'visit_flash');
        redirectToPage('visits', ['edit' => $admissionId]);
    }

    $status = $payload['discharge_date'] !== '' ? 'Discharged' : 'Admitted';
    $dischargeDate = $payload['discharge_date'] !== '' ? $payload['discharge_date'] : null;
    $bedNumber = $payload['bed_number'] !== '' ? $payload['bed_number'] : null;
    $diagnosis = $payload['diagnosis'] !== '' ? $payload['diagnosis'] : null;
    $notes = $payload['notes'] !== '' ? $payload['notes'] : null;
    $paymentDate = $payload['payment_date'] !== '' ? $payload['payment_date'] : null;
    $amount = (float)$payload['amount'];
    $visitId = (int)$existingRecord['visit_id'];
    $billId = (int)$existingRecord['bill_id'];

    $transaction = runTransaction(
        $conn,
        static function () use (
            $conn,
            $admissionId,
            $payload,
            $status,
            $dischargeDate,
            $bedNumber,
            $diagnosis,
            $notes,
            $paymentDate,
            $amount,
            $visitId,
            $billId
        ): void {
            $admissionStmt = mysqli_prepare(
                $conn,
                "
                    UPDATE admissions
                    SET
                        patient_id = ?,
                        doctor_id = ?,
                        admission_date = ?,
                        discharge_date = ?,
                        status = ?,
                        bed_number = ?
                    WHERE admission_id = ?
                "
            );

            if (!$admissionStmt) {
                throw new RuntimeException('Unable to prepare admission update.');
            }

            mysqli_stmt_bind_param(
                $admissionStmt,
                'iissssi',
                $payload['patient_id'],
                $payload['doctor_id'],
                $payload['admission_date'],
                $dischargeDate,
                $status,
                $bedNumber,
                $admissionId
            );

            executeStatementOrFail($admissionStmt);
            mysqli_stmt_close($admissionStmt);

            if ($visitId > 0) {
                $visitStmt = mysqli_prepare(
                    $conn,
                    "
                        UPDATE visit_history
                        SET
                            patient_id = ?,
                            doctor_id = ?,
                            visit_date = ?,
                            diagnosis = ?,
                            notes = ?
                        WHERE visit_id = ?
                    "
                );

                if (!$visitStmt) {
                    throw new RuntimeException('Unable to prepare visit history update.');
                }

                mysqli_stmt_bind_param(
                    $visitStmt,
                    'iisssi',
                    $payload['patient_id'],
                    $payload['doctor_id'],
                    $payload['visit_date'],
                    $diagnosis,
                    $notes,
                    $visitId
                );

                executeStatementOrFail($visitStmt);
                mysqli_stmt_close($visitStmt);
            } else {
                $visitStmt = mysqli_prepare(
                    $conn,
                    "
                        INSERT INTO visit_history (
                            admission_id,
                            patient_id,
                            doctor_id,
                            visit_date,
                            diagnosis,
                            notes
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    "
                );

                if (!$visitStmt) {
                    throw new RuntimeException('Unable to prepare visit history record.');
                }

                mysqli_stmt_bind_param(
                    $visitStmt,
                    'iiisss',
                    $admissionId,
                    $payload['patient_id'],
                    $payload['doctor_id'],
                    $payload['visit_date'],
                    $diagnosis,
                    $notes
                );

                executeStatementOrFail($visitStmt);
                mysqli_stmt_close($visitStmt);
            }

            if ($billId > 0) {
                $billingStmt = mysqli_prepare(
                    $conn,
                    "
                        UPDATE billing
                        SET
                            amount = ?,
                            payment_status = ?,
                            payment_date = ?
                        WHERE bill_id = ?
                    "
                );

                if (!$billingStmt) {
                    throw new RuntimeException('Unable to prepare billing update.');
                }

                mysqli_stmt_bind_param(
                    $billingStmt,
                    'dssi',
                    $amount,
                    $payload['payment_status'],
                    $paymentDate,
                    $billId
                );

                executeStatementOrFail($billingStmt);
                mysqli_stmt_close($billingStmt);
            } else {
                $billingStmt = mysqli_prepare(
                    $conn,
                    "
                        INSERT INTO billing (
                            admission_id,
                            amount,
                            payment_status,
                            payment_date
                        )
                        VALUES (?, ?, ?, ?)
                    "
                );

                if (!$billingStmt) {
                    throw new RuntimeException('Unable to prepare billing record.');
                }

                mysqli_stmt_bind_param(
                    $billingStmt,
                    'idss',
                    $admissionId,
                    $amount,
                    $payload['payment_status'],
                    $paymentDate
                );

                executeStatementOrFail($billingStmt);
                mysqli_stmt_close($billingStmt);
            }
        }
    );

    if (!$transaction['success']) {
        setSessionFormData('visit_form_data', $payload);
        setFlashMessage('error', 'Unable to update the visit record right now.', 'visit_flash');
        redirectToPage('visits', ['edit' => $admissionId]);
    }

    setFlashMessage('success', 'Visit record updated successfully.', 'visit_flash');
    redirectToPage('visits');
}

function deleteVisitRecord(mysqli $conn): void
{
    $admissionId = (int)($_POST['admission_id'] ?? 0);
    $record = getVisitRecordById($conn, $admissionId);

    if ($admissionId <= 0 || $record === null) {
        setFlashMessage('error', 'The selected visit record could not be found.', 'visit_flash');
        redirectToPage('visits');
    }

    $transaction = runTransaction(
        $conn,
        static function () use ($conn, $admissionId): void {
            $billingStmt = mysqli_prepare(
                $conn,
                "DELETE FROM billing WHERE admission_id = ?"
            );

            if (!$billingStmt) {
                throw new RuntimeException('Unable to prepare billing delete.');
            }

            mysqli_stmt_bind_param($billingStmt, 'i', $admissionId);
            executeStatementOrFail($billingStmt);
            mysqli_stmt_close($billingStmt);

            $visitStmt = mysqli_prepare(
                $conn,
                "DELETE FROM visit_history WHERE admission_id = ?"
            );

            if (!$visitStmt) {
                throw new RuntimeException('Unable to prepare visit history delete.');
            }

            mysqli_stmt_bind_param($visitStmt, 'i', $admissionId);
            executeStatementOrFail($visitStmt);
            mysqli_stmt_close($visitStmt);

            $admissionStmt = mysqli_prepare(
                $conn,
                "DELETE FROM admissions WHERE admission_id = ?"
            );

            if (!$admissionStmt) {
                throw new RuntimeException('Unable to prepare admission delete.');
            }

            mysqli_stmt_bind_param($admissionStmt, 'i', $admissionId);
            executeStatementOrFail($admissionStmt);
            mysqli_stmt_close($admissionStmt);
        }
    );

    if (!$transaction['success']) {
        setFlashMessage('error', 'Unable to delete the visit record right now.', 'visit_flash');
        redirectToPage('visits');
    }

    setFlashMessage('success', 'Visit record deleted successfully.', 'visit_flash');
    redirectToPage('visits');
}

function handleVisitActions(mysqli $conn): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (isset($_POST['create_visit'])) {
        createVisitRecord($conn);
        return;
    }

    if (isset($_POST['update_visit'])) {
        updateVisitRecord($conn);
        return;
    }

    if (isset($_POST['delete_visit'])) {
        deleteVisitRecord($conn);
        return;
    }
}
