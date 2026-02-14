-- MySQL 8.0 Schema for Pharmaceutical Management System
-- Password: 252526

CREATE DATABASE IF NOT EXISTS pharma_db;
USE pharma_db;

-- Module 1: Drug Master & Inventory Structuring
CREATE TABLE IF NOT EXISTS drugs (
    drug_id INT PRIMARY KEY AUTO_INCREMENT,
    drug_name VARCHAR(100) NOT NULL,
    generic_name VARCHAR(100),
    category VARCHAR(50),
    manufacturer VARCHAR(100),
    unit_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_drug_name (drug_name),
    INDEX idx_category (category)
);

CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT PRIMARY KEY AUTO_INCREMENT,
    drug_id INT,
    batch_number VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    expiry_date DATE NOT NULL,
    mfg_date DATE,
    location VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drug_id) REFERENCES drugs(drug_id) ON DELETE CASCADE,
    INDEX idx_expiry (expiry_date),
    INDEX idx_batch (batch_number)
);

-- Module 2: Prescription Entry & Validation Workflow
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_name VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) UNIQUE,
    specialization VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_license (license_number)
);

CREATE TABLE IF NOT EXISTS patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_name VARCHAR(100) NOT NULL,
    age INT,
    gender VARCHAR(10),
    contact_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_number VARCHAR(50) UNIQUE NOT NULL,
    doctor_id INT,
    patient_id INT,
    prescription_date DATE NOT NULL,
    diagnosis TEXT,
    status ENUM('active', 'dispensed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    INDEX idx_prescription_date (prescription_date),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS prescription_details (
    detail_id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT,
    drug_id INT,
    dosage VARCHAR(50),
    frequency VARCHAR(50),
    duration VARCHAR(50),
    quantity INT,
    instructions TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (drug_id) REFERENCES drugs(drug_id),
    INDEX idx_drug (drug_id)
);

-- Module 3: Stock Consistency & Transaction Control
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    transaction_type ENUM('issue', 'return', 'adjustment') NOT NULL,
    prescription_id INT,
    inventory_id INT,
    quantity INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id),
    FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_type (transaction_type)
);

CREATE TABLE IF NOT EXISTS transaction_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT,
    old_quantity INT,
    new_quantity INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(50),
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
);

-- Insert sample data
INSERT INTO doctors (doctor_name, license_number, specialization, contact_number, email) VALUES
('Dr. Sarah Johnson', 'LIC123456', 'Cardiology', '9876543210', 'sarah.j@hospital.com'),
('Dr. Michael Chen', 'LIC789012', 'Neurology', '9876543211', 'michael.c@hospital.com');

INSERT INTO patients (patient_name, age, gender, contact_number, address) VALUES
('John Doe', 45, 'Male', '9988776655', '123 Main St, City'),
('Jane Smith', 32, 'Female', '9988776644', '456 Oak Ave, Town');

INSERT INTO drugs (drug_name, generic_name, category, manufacturer, unit_price) VALUES
('Paracetamol 500mg', 'Acetaminophen', 'Analgesic', 'PharmaCorp', 2.50),
('Amoxicillin 250mg', 'Amoxicillin', 'Antibiotic', 'MediLabs', 5.75),
('Omeprazole 20mg', 'Omeprazole', 'Antacid', 'HealthPharma', 3.25);

INSERT INTO inventory (drug_id, batch_number, quantity, expiry_date, mfg_date, location) VALUES
(1, 'BATCH001', 1000, '2025-12-31', '2023-01-01', 'Shelf A-01'),
(2, 'BATCH002', 500, '2024-06-30', '2023-06-01', 'Shelf B-02'),
(3, 'BATCH003', 750, '2025-03-31', '2023-03-01', 'Shelf A-02');