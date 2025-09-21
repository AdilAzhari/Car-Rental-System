-- Create database if not exists
CREATE DATABASE IF NOT EXISTS car_rental_system;

-- Create user if not exists
CREATE USER IF NOT EXISTS 'car_rental_user'@'%' IDENTIFIED BY 'secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON car_rental_system.* TO 'car_rental_user'@'%';

-- Grant privileges for testing database
GRANT ALL PRIVILEGES ON car_rental_test.* TO 'car_rental_user'@'%';

-- Flush privileges
FLUSH PRIVILEGES;