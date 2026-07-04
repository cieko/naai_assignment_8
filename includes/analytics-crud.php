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

function getDashboardHeroStats(mysqli $conn): array
{
    $stats = [
        'active_patients' => 0,
        'admissions' => 0,
        'discharges' => 0,
    ];

    $sql = "
        SELECT
            COUNT(DISTINCT CASE
                WHEN a.status = 'Admitted' THEN a.patient_id
                ELSE NULL
            END) AS active_patients,
            COUNT(a.admission_id) AS admissions,
            SUM(CASE
                WHEN a.status = 'Discharged' THEN 1
                ELSE 0
            END) AS discharges
        FROM admissions a
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return $stats;
    }

    $row = mysqli_fetch_assoc($result) ?: [];

    $stats['active_patients'] = (int)($row['active_patients'] ?? 0);
    $stats['admissions'] = (int)($row['admissions'] ?? 0);
    $stats['discharges'] = (int)($row['discharges'] ?? 0);

    mysqli_free_result($result);

    return $stats;
}

function getDashboardOperationsOverview(mysqli $conn): array
{
    $overview = [
        'total_visits' => 0,
        'active_admissions' => 0,
        'paid_bills' => 0,
        'pending_bills' => 0,
        'tracked_beds' => 0,
        'occupied_beds' => 0,
        'occupancy_rate' => 0,
    ];

    $billingSql = getLatestBillingSnapshotSql();

    $sql = "
        SELECT
            (
                SELECT COUNT(*)
                FROM visit_history
            ) AS total_visits,
            (
                SELECT COUNT(*)
                FROM admissions
                WHERE status = 'Admitted'
            ) AS active_admissions,
            (
                SELECT COUNT(*)
                FROM (
                    $billingSql
                ) latest_billing
                WHERE latest_billing.payment_status = 'Paid'
            ) AS paid_bills,
            (
                SELECT COUNT(*)
                FROM (
                    $billingSql
                ) latest_billing
                WHERE latest_billing.payment_status = 'Pending'
            ) AS pending_bills,
            (
                SELECT COUNT(DISTINCT bed_number)
                FROM admissions
                WHERE TRIM(COALESCE(bed_number, '')) <> ''
            ) AS tracked_beds,
            (
                SELECT COUNT(DISTINCT bed_number)
                FROM admissions
                WHERE status = 'Admitted'
                    AND TRIM(COALESCE(bed_number, '')) <> ''
            ) AS occupied_beds
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return $overview;
    }

    $row = mysqli_fetch_assoc($result) ?: [];

    $overview['total_visits'] = (int)($row['total_visits'] ?? 0);
    $overview['active_admissions'] = (int)($row['active_admissions'] ?? 0);
    $overview['paid_bills'] = (int)($row['paid_bills'] ?? 0);
    $overview['pending_bills'] = (int)($row['pending_bills'] ?? 0);
    $overview['tracked_beds'] = (int)($row['tracked_beds'] ?? 0);
    $overview['occupied_beds'] = (int)($row['occupied_beds'] ?? 0);
    $overview['occupancy_rate'] = $overview['tracked_beds'] > 0
        ? (int)round(($overview['occupied_beds'] / $overview['tracked_beds']) * 100)
        : 0;

    mysqli_free_result($result);

    return $overview;
}

function getPatientsByAge(mysqli $conn): array
{
    $sql = "
        SELECT
            patient_id,
            age
        FROM patients
        ORDER BY patient_id
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [];
    }

    $patients = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $age = (int)$row['age'];

        if ($age <= 12) {
            $color = '#9fd8ff';
        } elseif ($age <= 24) {
            $color = '#cfc7ff';
        } elseif ($age <= 39) {
            $color = '#ffd5a0';
        } elseif ($age <= 59) {
            $color = '#8df0b6';
        } else {
            $color = '#22d4b8';
        }

        $patients[] = [
            'id' => (int)$row['patient_id'],
            'age' => $age,
            'value' => max(12, $age + 10),
            'color' => $color,
        ];
    }

    return $patients;
}

function getPatientsByGender(mysqli $conn): array
{
    $sql = "
        SELECT
            gender,
            COUNT(*) AS total
        FROM patients
        GROUP BY gender
    ";

    $result = mysqli_query($conn, $sql);

    $gender = [
        'Male' => 0,
        'Female' => 0,
        'Other' => 0,
    ];

    if (!$result) {
        return $gender;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['gender'];

        if (array_key_exists($key, $gender)) {
            $gender[$key] = (int)$row['total'];
        }
    }

    return $gender;
}

function getLatestBillingSnapshotSql(): string
{
    return "
        SELECT
            b1.admission_id,
            b1.amount,
            b1.payment_status,
            b1.payment_date
        FROM billing b1
        INNER JOIN (
            SELECT
                admission_id,
                MAX(bill_id) AS bill_id
            FROM billing
            GROUP BY admission_id
        ) latest_bill
            ON latest_bill.bill_id = b1.bill_id
    ";
}

function getDiseaseOverview(mysqli $conn): array
{
    $overview = [
        'tracked_patients' => 0,
        'unique_diseases' => 0,
        'active_cases' => 0,
        'discharged_cases' => 0,
        'total_revenue' => 0.0,
        'top_disease_name' => 'No disease data',
        'top_disease_count' => 0,
    ];

    $billingSql = getLatestBillingSnapshotSql();

    $summarySql = "
        SELECT
            COUNT(DISTINCT p.patient_id) AS tracked_patients,
            COUNT(DISTINCT TRIM(p.disease)) AS unique_diseases,
            SUM(CASE WHEN a.status = 'Admitted' THEN 1 ELSE 0 END) AS active_cases,
            SUM(CASE WHEN a.status = 'Discharged' THEN 1 ELSE 0 END) AS discharged_cases,
            COALESCE(SUM(COALESCE(b.amount, 0)), 0) AS total_revenue
        FROM patients p
        LEFT JOIN admissions a
            ON p.patient_id = a.patient_id
        LEFT JOIN (
            $billingSql
        ) b
            ON b.admission_id = a.admission_id
        WHERE TRIM(COALESCE(p.disease, '')) <> ''
    ";

    $summaryResult = mysqli_query($conn, $summarySql);

    if ($summaryResult) {
        $row = mysqli_fetch_assoc($summaryResult) ?: [];

        $overview['tracked_patients'] = (int)($row['tracked_patients'] ?? 0);
        $overview['unique_diseases'] = (int)($row['unique_diseases'] ?? 0);
        $overview['active_cases'] = (int)($row['active_cases'] ?? 0);
        $overview['discharged_cases'] = (int)($row['discharged_cases'] ?? 0);
        $overview['total_revenue'] = (float)($row['total_revenue'] ?? 0);

        mysqli_free_result($summaryResult);
    }

    $topDiseaseSql = "
        SELECT
            TRIM(disease) AS disease_name,
            COUNT(*) AS patient_total
        FROM patients
        WHERE TRIM(COALESCE(disease, '')) <> ''
        GROUP BY TRIM(disease)
        ORDER BY patient_total DESC, disease_name ASC
        LIMIT 1
    ";

    $topDiseaseResult = mysqli_query($conn, $topDiseaseSql);

    if ($topDiseaseResult) {
        $topDisease = mysqli_fetch_assoc($topDiseaseResult) ?: null;

        if ($topDisease !== null) {
            $overview['top_disease_name'] = (string)$topDisease['disease_name'];
            $overview['top_disease_count'] = (int)$topDisease['patient_total'];
        }

        mysqli_free_result($topDiseaseResult);
    }

    return $overview;
}

function getDiseaseDistribution(
    mysqli $conn,
    int $limit = 6
): array {
    $limit = max(1, $limit);
    $billingSql = getLatestBillingSnapshotSql();

    $sql = "
        SELECT
            TRIM(p.disease) AS disease_name,
            COUNT(DISTINCT p.patient_id) AS patient_total,
            COUNT(a.admission_id) AS admission_total,
            SUM(CASE WHEN a.status = 'Admitted' THEN 1 ELSE 0 END) AS active_cases,
            SUM(CASE WHEN a.status = 'Discharged' THEN 1 ELSE 0 END) AS discharged_cases,
            COALESCE(SUM(COALESCE(b.amount, 0)), 0) AS total_revenue
        FROM patients p
        LEFT JOIN admissions a
            ON p.patient_id = a.patient_id
        LEFT JOIN (
            $billingSql
        ) b
            ON b.admission_id = a.admission_id
        WHERE TRIM(COALESCE(p.disease, '')) <> ''
        GROUP BY TRIM(p.disease)
        ORDER BY
            patient_total DESC,
            admission_total DESC,
            disease_name ASC
        LIMIT $limit
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [];
    }

    $distribution = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $distribution[] = [
            'disease_name' => (string)$row['disease_name'],
            'patient_total' => (int)$row['patient_total'],
            'admission_total' => (int)$row['admission_total'],
            'active_cases' => (int)$row['active_cases'],
            'discharged_cases' => (int)$row['discharged_cases'],
            'total_revenue' => (float)$row['total_revenue'],
        ];
    }

    mysqli_free_result($result);

    return $distribution;
}

function getDiseaseTrendSeries(
    mysqli $conn,
    int $months = 6,
    int $diseaseLimit = 4
): array {
    $months = max(1, $months);
    $diseaseLimit = max(1, $diseaseLimit);

    $startMonth = (new DateTimeImmutable('first day of this month'))
        ->modify('-' . ($months - 1) . ' months');

    $monthMeta = [];
    $monthCursor = $startMonth;

    for ($index = 0; $index < $months; $index += 1) {
        $monthMeta[] = [
            'key' => $monthCursor->format('Y-m-01'),
            'label' => $monthCursor->format('M'),
        ];

        $monthCursor = $monthCursor->modify('+1 month');
    }

    $sql = "
        SELECT
            DATE_FORMAT(a.admission_date, '%Y-%m-01') AS month_key,
            TRIM(p.disease) AS disease_name,
            COUNT(a.admission_id) AS total_cases
        FROM admissions a
        INNER JOIN patients p
            ON a.patient_id = p.patient_id
        WHERE a.admission_date >= '" . $startMonth->format('Y-m-d') . "'
            AND TRIM(COALESCE(p.disease, '')) <> ''
        GROUP BY
            DATE_FORMAT(a.admission_date, '%Y-%m-01'),
            TRIM(p.disease)
        ORDER BY
            month_key ASC,
            total_cases DESC,
            disease_name ASC
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [
            'months' => $monthMeta,
            'series' => [],
        ];
    }

    $seriesMatrix = [];
    $diseaseTotals = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $diseaseName = (string)$row['disease_name'];
        $monthKey = (string)$row['month_key'];
        $totalCases = (int)$row['total_cases'];

        if ($diseaseName === '' || $monthKey === '') {
            continue;
        }

        $seriesMatrix[$diseaseName][$monthKey] = $totalCases;
        $diseaseTotals[$diseaseName] = ($diseaseTotals[$diseaseName] ?? 0) + $totalCases;
    }

    mysqli_free_result($result);

    $rankedDiseases = [];

    foreach ($diseaseTotals as $diseaseName => $totalCases) {
        $rankedDiseases[] = [
            'disease_name' => $diseaseName,
            'total_cases' => $totalCases,
        ];
    }

    usort(
        $rankedDiseases,
        static function (array $left, array $right): int {
            $byTotal = $right['total_cases'] <=> $left['total_cases'];

            if ($byTotal !== 0) {
                return $byTotal;
            }

            return strcmp($left['disease_name'], $right['disease_name']);
        }
    );

    $selectedDiseases = array_slice($rankedDiseases, 0, $diseaseLimit);
    $series = [];

    foreach ($selectedDiseases as $disease) {
        $diseaseName = $disease['disease_name'];
        $values = [];

        foreach ($monthMeta as $month) {
            $values[] = [
                'key' => $month['key'],
                'label' => $month['label'],
                'value' => (int)($seriesMatrix[$diseaseName][$month['key']] ?? 0),
            ];
        }

        $series[] = [
            'disease_name' => $diseaseName,
            'total_cases' => (int)$disease['total_cases'],
            'values' => $values,
        ];
    }

    return [
        'months' => $monthMeta,
        'series' => $series,
    ];
}

function getDiseaseDepartmentBreakdown(
    mysqli $conn,
    int $diseaseLimit = 5
): array {
    $diseaseLimit = max(1, $diseaseLimit);

    $sql = "
        SELECT
            TRIM(p.disease) AS disease_name,
            dep.department_name,
            COUNT(a.admission_id) AS total_cases
        FROM admissions a
        INNER JOIN patients p
            ON a.patient_id = p.patient_id
        INNER JOIN doctors d
            ON a.doctor_id = d.doctor_id
        INNER JOIN departments dep
            ON d.department_id = dep.department_id
        WHERE TRIM(COALESCE(p.disease, '')) <> ''
        GROUP BY
            TRIM(p.disease),
            dep.department_name
        ORDER BY
            total_cases DESC,
            disease_name ASC,
            dep.department_name ASC
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [
            'departments' => [],
            'rows' => [],
        ];
    }

    $diseaseTotals = [];
    $departmentTotals = [];
    $matrix = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $diseaseName = (string)$row['disease_name'];
        $departmentName = (string)$row['department_name'];
        $totalCases = (int)$row['total_cases'];

        if ($diseaseName === '' || $departmentName === '') {
            continue;
        }

        $diseaseTotals[$diseaseName] = ($diseaseTotals[$diseaseName] ?? 0) + $totalCases;
        $departmentTotals[$departmentName] = ($departmentTotals[$departmentName] ?? 0) + $totalCases;
        $matrix[$diseaseName][$departmentName] = $totalCases;
    }

    mysqli_free_result($result);

    $rankedDiseases = [];

    foreach ($diseaseTotals as $diseaseName => $totalCases) {
        $rankedDiseases[] = [
            'disease_name' => $diseaseName,
            'total_cases' => $totalCases,
        ];
    }

    usort(
        $rankedDiseases,
        static function (array $left, array $right): int {
            $byTotal = $right['total_cases'] <=> $left['total_cases'];

            if ($byTotal !== 0) {
                return $byTotal;
            }

            return strcmp($left['disease_name'], $right['disease_name']);
        }
    );

    $selectedDiseases = array_slice($rankedDiseases, 0, $diseaseLimit);
    $selectedDiseaseNames = array_column($selectedDiseases, 'disease_name');

    $rankedDepartments = [];

    foreach ($departmentTotals as $departmentName => $totalCases) {
        $rankedDepartments[] = [
            'department_name' => $departmentName,
            'total_cases' => $totalCases,
        ];
    }

    usort(
        $rankedDepartments,
        static function (array $left, array $right): int {
            $byTotal = $right['total_cases'] <=> $left['total_cases'];

            if ($byTotal !== 0) {
                return $byTotal;
            }

            return strcmp($left['department_name'], $right['department_name']);
        }
    );

    $rows = [];

    foreach ($selectedDiseaseNames as $diseaseName) {
        $segments = [];

        foreach ($rankedDepartments as $department) {
            $departmentName = $department['department_name'];
            $totalCases = (int)($matrix[$diseaseName][$departmentName] ?? 0);

            if ($totalCases <= 0) {
                continue;
            }

            $segments[] = [
                'department_name' => $departmentName,
                'total_cases' => $totalCases,
                'percentage' => $diseaseTotals[$diseaseName] > 0
                    ? round(($totalCases / $diseaseTotals[$diseaseName]) * 100, 1)
                    : 0,
            ];
        }

        $rows[] = [
            'disease_name' => $diseaseName,
            'total_cases' => (int)$diseaseTotals[$diseaseName],
            'segments' => $segments,
        ];
    }

    return [
        'departments' => $rankedDepartments,
        'rows' => $rows,
    ];
}

function getRecentDiseaseCases(
    mysqli $conn,
    int $limit = 8
): array {
    $limit = max(1, $limit);
    $billingSql = getLatestBillingSnapshotSql();

    $sql = "
        SELECT
            a.admission_id,
            p.patient_id,
            p.patient_name,
            TRIM(p.disease) AS disease_name,
            d.doctor_name,
            dep.department_name,
            a.admission_date,
            a.discharge_date,
            a.status,
            COALESCE(b.amount, 0) AS amount,
            COALESCE(b.payment_status, 'Pending') AS payment_status
        FROM admissions a
        INNER JOIN patients p
            ON a.patient_id = p.patient_id
        INNER JOIN doctors d
            ON a.doctor_id = d.doctor_id
        INNER JOIN departments dep
            ON d.department_id = dep.department_id
        LEFT JOIN (
            $billingSql
        ) b
            ON b.admission_id = a.admission_id
        WHERE TRIM(COALESCE(p.disease, '')) <> ''
        ORDER BY
            a.admission_date DESC,
            a.admission_id DESC
        LIMIT $limit
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [];
    }

    $cases = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_free_result($result);

    return $cases;
}

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
