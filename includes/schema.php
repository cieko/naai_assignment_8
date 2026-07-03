<?php

function initialiseDatabaseTable(mysqli $conn): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS departments (

            department_id INT AUTO_INCREMENT PRIMARY KEY,
            department_name VARCHAR(100) NOT NULL UNIQUE

        );

        CREATE TABLE IF NOT EXISTS doctors (

            doctor_id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_name VARCHAR(100) NOT NULL,
            department_id INT NOT NULL,
            specialization VARCHAR(100),
            phone VARCHAR(20),

            CONSTRAINT fk_doctor_department
                FOREIGN KEY (department_id)
                REFERENCES departments(department_id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS patients (

            patient_id INT AUTO_INCREMENT PRIMARY KEY,
            patient_name VARCHAR(100) NOT NULL,
            age INT NOT NULL CHECK (age >= 0),
            gender ENUM('Male', 'Female', 'Other') NOT NULL,
            disease VARCHAR(150),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_patient_name (patient_name)
        );

        CREATE TABLE IF NOT EXISTS admissions (

            admission_id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            admission_date DATE NOT NULL,
            discharge_date DATE DEFAULT NULL,
            status ENUM('Admitted', 'Discharged')
                DEFAULT 'Admitted',
            bed_number VARCHAR(20),

            INDEX idx_admission_date (admission_date),

            CONSTRAINT fk_admission_patient
                FOREIGN KEY (patient_id)
                REFERENCES patients(patient_id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,

            CONSTRAINT fk_admission_doctor
                FOREIGN KEY (doctor_id)
                REFERENCES doctors(doctor_id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS visit_history (

            visit_id INT AUTO_INCREMENT PRIMARY KEY,
            admission_id INT DEFAULT NULL,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            visit_date DATE NOT NULL,
            diagnosis TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,

            INDEX idx_visit_date (visit_date),

            CONSTRAINT fk_visit_admission
                FOREIGN KEY (admission_id)
                REFERENCES admissions(admission_id)
                ON UPDATE CASCADE
                ON DELETE SET NULL,

            CONSTRAINT fk_visit_patient
                FOREIGN KEY (patient_id)
                REFERENCES patients(patient_id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,

            CONSTRAINT fk_visit_doctor
                FOREIGN KEY (doctor_id)
                REFERENCES doctors(doctor_id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS billing (
        
            bill_id INT AUTO_INCREMENT PRIMARY KEY,
            admission_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL CHECK (amount >= 0),
            payment_status ENUM(
                'Pending',
                'Partial',
                'Paid'
            ) DEFAULT 'Pending',
            payment_date DATE DEFAULT NULL,

            CONSTRAINT fk_bill_admission
                FOREIGN KEY (admission_id)
                REFERENCES admissions(admission_id)
                ON UPDATE CASCADE
                ON DELETE CASCADE
        );
    ";

    if (!mysqli_multi_query($conn, $sql)) {
        die('Database initialization failed: ' . mysqli_error($conn));
    }

    // Flush all result sets
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
}