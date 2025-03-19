-- Create database
CREATE DATABASE blood_heroes;
USE blood_heroes;

-- Create users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile_number VARCHAR(15) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create divisions table
CREATE TABLE divisions (
    division_id INT AUTO_INCREMENT PRIMARY KEY,
    division_name VARCHAR(50) NOT NULL
);

-- Create districts table
CREATE TABLE districts (
    district_id INT AUTO_INCREMENT PRIMARY KEY,
    district_name VARCHAR(50) NOT NULL,
    division_id INT,
    FOREIGN KEY (division_id) REFERENCES divisions(division_id)
);

-- Create donors table
CREATE TABLE donors (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    age INT NOT NULL,
    weight VARCHAR(20) NOT NULL,
    last_donation_date DATE,
    division_id INT,
    district_id INT,
    area VARCHAR(255) NOT NULL,
    is_available BOOLEAN GENERATED ALWAYS AS (
        CASE 
            WHEN last_donation_date IS NULL THEN TRUE
            WHEN DATEDIFF(CURRENT_DATE, last_donation_date) >= 90 THEN TRUE
            ELSE FALSE
        END
    ) STORED,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (division_id) REFERENCES divisions(division_id),
    FOREIGN KEY (district_id) REFERENCES districts(district_id)
);

-- Create blood requests table
CREATE TABLE blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(100) NOT NULL,
    division_id INT,
    district_id INT,
    hospital VARCHAR(255) NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    reason TEXT,
    contact_number VARCHAR(15) NOT NULL,
    bags_needed INT NOT NULL,
    needed_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES divisions(division_id),
    FOREIGN KEY (district_id) REFERENCES districts(district_id)
);

-- Insert Bangladesh divisions
INSERT INTO divisions (division_name) VALUES 
('Dhaka'), ('Chittagong'), ('Rajshahi'), ('Khulna'), 
('Barisal'), ('Sylhet'), ('Rangpur'), ('Mymensingh');

-- Insert some sample districts (abbreviated list)
INSERT INTO districts (district_name, division_id) VALUES 
-- Dhaka Division
('Dhaka', 1), ('Gazipur', 1), ('Narayanganj', 1), ('Tangail', 1),
-- Chittagong Division
('Chittagong', 2), ('Cox\'s Bazar', 2), ('Comilla', 2),
-- Rajshahi Division
('Rajshahi', 3), ('Bogra', 3), ('Pabna', 3),
-- Khulna Division
('Khulna', 4), ('Jessore', 4), ('Kushtia', 4),
-- Barisal Division
('Barisal', 5), ('Bhola', 5), ('Patuakhali', 5),
-- Sylhet Division
('Sylhet', 6), ('Moulvibazar', 6), ('Habiganj', 6),
-- Rangpur Division
('Rangpur', 7), ('Dinajpur', 7), ('Kurigram', 7),
-- Mymensingh Division
('Mymensingh', 8), ('Jamalpur', 8), ('Netrokona', 8);