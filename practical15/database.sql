-- ==============================================================================
-- StudentHub Portal - Comprehensive Final Database Dump (Practical 15)
-- Database: studenthub
-- Engine: MySQL / MariaDB (XAMPP)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS studenthub;
USE studenthub;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student') DEFAULT 'student',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `students`
-- --------------------------------------------------------
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    course VARCHAR(50),
    phone VARCHAR(15),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `events`
-- --------------------------------------------------------
CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(100) NOT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(100),
    description TEXT,
    category VARCHAR(50) DEFAULT 'General',
    status ENUM('open','closed') DEFAULT 'open',
    poster VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `registrations`
-- --------------------------------------------------------
CREATE TABLE registrations (
    reg_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Seed Data: Users (Password is 'password' hashed with BCRYPT)
-- --------------------------------------------------------
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@studenthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('vraj', 'vraj@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('riya', 'riya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- --------------------------------------------------------
-- Seed Data: Students
-- --------------------------------------------------------
INSERT INTO students (name, email, course, phone, status) VALUES
('Vraj Patel', 'vraj@example.com', 'BCA', '9876543210', 'active'),
('Riya Shah', 'riya@example.com', 'MCA', '9876543211', 'active'),
('Arjun Mehta', 'arjun@example.com', 'BCA', '9876543212', 'active'),
('Priya Sharma', 'priya@example.com', 'MSc IT', '9876543213', 'active'),
('Karan Joshi', 'karan@example.com', 'BCA', '9876543214', 'active'),
('Neha Gupta', 'neha@example.com', 'MCA', '9876543215', 'active'),
('Rohan Desai', 'rohan@example.com', 'BCA', '9876543216', 'active'),
('Anjali Verma', 'anjali@example.com', 'MSc IT', '9876543217', 'active'),
('Sahil Kumar', 'sahil@example.com', 'MCA', '9876543218', 'inactive'),
('Pooja Nair', 'pooja@example.com', 'BCA', '9876543219', 'active');

-- --------------------------------------------------------
-- Seed Data: Events
-- --------------------------------------------------------
INSERT INTO events (event_name, event_date, location, description, category, status, poster) VALUES
('Tech Fest 2026', '2026-11-15', 'Main Auditorium', 'Annual technological festival and project showcase', 'Technical', 'open', 'poster_6ad698ac5b802.png'),
('Coding Hackathon', '2026-11-20', 'Lab 1 & 2', '24-hour competitive programming and AI hackathon', 'Technical', 'open', NULL),
('Full-Stack Workshop', '2026-12-05', 'Seminar Hall', 'Hands-on full stack PHP, MySQL & modern frontend workshop', 'Workshop', 'open', NULL),
('Annual Cultural Night', '2026-12-10', 'Open Air Theatre', 'Music, dance, and cultural performance night', 'Cultural', 'open', NULL),
('Inter-College Sports Meet', '2026-12-15', 'University Sports Ground', 'Annual track, football, and cricket tournament', 'Sports', 'open', NULL);

-- --------------------------------------------------------
-- Seed Data: Event Registrations
-- --------------------------------------------------------
INSERT INTO registrations (student_id, event_id) VALUES
(1, 1),
(1, 3),
(2, 1),
(3, 2),
(4, 2),
(5, 3),
(6, 1),
(7, 2),
(8, 3),
(10, 5);

-- --------------------------------------------------------
-- Seed Data: Initial Audit Logs
-- --------------------------------------------------------
INSERT INTO audit_logs (user_id, action, details) VALUES
(1, 'System Initialization', 'StudentHub integrated database schema installed successfully'),
(1, 'Event Published', 'Tech Fest 2026 added with poster'),
(2, 'Event Registration', 'Student #1 registered for Tech Fest 2026'),
(1, 'Role Updated', 'Updated user permissions for administrative panel');
