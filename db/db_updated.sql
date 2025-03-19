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
    is_available BOOLEAN DEFAULT TRUE,  -- Default is TRUE, will be updated by triggers
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

-- Insert districts for each division
-- Dhaka Division
INSERT INTO districts (district_name, division_id) VALUES 
('Dhaka', 1), ('Faridpur', 1), ('Gazipur', 1), ('Gopalganj', 1),
('Kishoreganj', 1), ('Madaripur', 1), ('Manikganj', 1), ('Munshiganj', 1),
('Narsingdi', 1), ('Rajbari', 1), ('Shariatpur', 1), ('Tangail', 1);

-- Chittagong Division
INSERT INTO districts (district_name, division_id) VALUES 
('Bandarban', 2), ('Brahmanbaria', 2), ('Chandpur', 2), ('Chittagong', 2),
('Cox\'s Bazar', 2), ('Cumilla', 2), ('Feni', 2), ('Khagrachari', 2),
('Lakshmipur', 2), ('Noakhali', 2), ('Rangamati', 2);

-- Rajshahi Division
INSERT INTO districts (district_name, division_id) VALUES 
('Bogura', 3), ('Chapainawabganj', 3), ('Naogaon', 3), ('Natore', 3),
('Pabna', 3), ('Rajshahi', 3), ('Sirajganj', 3);

-- Khulna Division
INSERT INTO districts (district_name, division_id) VALUES 
('Bagerhat', 4), ('Chuadanga', 4), ('Jhenaidah', 4), ('Jessore', 4),
('Khulna', 4), ('Kumarkhali', 4), ('Meherpur', 4), ('Satkhira', 4), ('Shailkupa', 4);

-- Barisal Division
INSERT INTO districts (district_name, division_id) VALUES 
('Barguna', 5), ('Barisal', 5), ('Bhola', 5), ('Jhalokathi', 5),
('Patuakhali', 5), ('Pirojpur', 5);

-- Sylhet Division
INSERT INTO districts (district_name, division_id) VALUES 
('Habiganj', 6), ('Moulvibazar', 6), ('Sunamganj', 6), ('Sylhet', 6);

-- Rangpur Division
INSERT INTO districts (district_name, division_id) VALUES 
('Kurigram', 7), ('Lalmonirhat', 7), ('Nilphamari', 7), ('Pirganj', 7),
('Rangpur', 7), ('Thakurgaon', 7), ('Gaibandha', 7), ('Dinajpur', 7);

-- Mymensingh Division
INSERT INTO districts (district_name, division_id) VALUES 
('Jamalpur', 8), ('Mymensingh', 8), ('Netrokona', 8), ('Sherpur', 8);

-- Create trigger to update 'is_available' before insertion
DELIMITER //
CREATE TRIGGER before_insert_donor
BEFORE INSERT ON donors
FOR EACH ROW
BEGIN
    IF NEW.last_donation_date IS NULL THEN
        SET NEW.is_available = TRUE;
    ELSE
        IF DATEDIFF(CURRENT_DATE, NEW.last_donation_date) >= 90 THEN
            SET NEW.is_available = TRUE;
        ELSE
            SET NEW.is_available = FALSE;
        END IF;
    END IF;
END;
//
DELIMITER ;

-- Create trigger to update 'is_available' before update
DELIMITER //
CREATE TRIGGER before_update_donor
BEFORE UPDATE ON donors
FOR EACH ROW
BEGIN
    IF NEW.last_donation_date IS NULL THEN
        SET NEW.is_available = TRUE;
    ELSE
        IF DATEDIFF(CURRENT_DATE, NEW.last_donation_date) >= 90 THEN
            SET NEW.is_available = TRUE;
        ELSE
            SET NEW.is_available = FALSE;
        END IF;
    END IF;
END;
//
DELIMITER ;
