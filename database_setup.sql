-- Database Setup for Employee Profile System
-- Run this SQL script to create the database and table

CREATE DATABASE IF NOT EXISTS employee_system;
USE employee_system;

CREATE TABLE IF NOT EXISTS employee_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    employee_name VARCHAR(100) NOT NULL,
    clock_in TIME NOT NULL,
    clock_out TIME NOT NULL,
    total_hours DECIMAL(5,2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_date (date)
);

-- Sample data for testing
INSERT INTO employee_attendance (employee_id, employee_name, clock_in, clock_out, total_hours, date) VALUES
('EMP001', 'John Doe', '09:00:00', '17:30:00', 8.50, '2024-01-15'),
('EMP002', 'Jane Smith', '08:30:00', '16:30:00', 8.00, '2024-01-15'),
('EMP003', 'Mike Johnson', '09:15:00', '18:00:00', 8.75, '2024-01-15'),
('EMP001', 'John Doe', '09:05:00', '17:25:00', 8.33, '2024-01-16'),
('EMP002', 'Jane Smith', '08:45:00', '17:00:00', 8.25, '2024-01-16');
