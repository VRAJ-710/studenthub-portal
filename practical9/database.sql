CREATE DATABASE IF NOT EXISTS studenthub;
USE studenthub;

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
);

CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    course VARCHAR(50),
    phone VARCHAR(15),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
);

CREATE TABLE registrations (
    reg_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    event_id INT,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@studenthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('vraj', 'vraj@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('riya', 'riya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO students (name, email, course, phone) VALUES
('Vraj Patel', 'vraj@example.com', 'BCA', '9876543210'),
('Riya Shah', 'riya@example.com', 'MCA', '9876543211'),
('Arjun Mehta', 'arjun@example.com', 'BCA', '9876543212'),
('Priya Sharma', 'priya@example.com', 'MSc IT', '9876543213'),
('Karan Joshi', 'karan@example.com', 'BCA', '9876543214'),
('Neha Gupta', 'neha@example.com', 'MCA', '9876543215'),
('Rohan Desai', 'rohan@example.com', 'BCA', '9876543216'),
('Anjali Verma', 'anjali@example.com', 'MSc IT', '9876543217'),
('Sahil Kumar', 'sahil@example.com', 'MCA', '9876543218'),
('Pooja Nair', 'pooja@example.com', 'BCA', '9876543219'),
('Amit Rao', 'amit@example.com', 'MSc IT', '9876543220'),
('Divya Iyer', 'divya@example.com', 'BCA', '9876543221'),
('Rahul Singh', 'rahul@example.com', 'MCA', '9876543222'),
('Meera Das', 'meera@example.com', 'BCA', '9876543223'),
('Vikram Chauhan', 'vikram@example.com', 'MSc IT', '9876543224'),
('Sneha Pillai', 'sneha@example.com', 'MCA', '9876543225'),
('Jay Trivedi', 'jay@example.com', 'BCA', '9876543226'),
('Tanvi Bhatt', 'tanvi@example.com', 'MSc IT', '9876543227'),
('Harsh Soni', 'harsh@example.com', 'MCA', '9876543228'),
('Kavya Pandey', 'kavya@example.com', 'BCA', '9876543229');

INSERT INTO events (event_name, event_date, location, description, category) VALUES
('Tech Fest 2026', '2026-11-15', 'Auditorium', 'Annual technology festival', 'Technical'),
('Coding Contest', '2026-11-20', 'Lab 1', 'Competitive programming event', 'Technical'),
('Workshop on AI', '2026-12-05', 'Seminar Hall', 'Hands-on AI workshop', 'Workshop'),
('Cultural Night', '2026-12-10', 'Open Ground', 'Annual cultural program', 'Cultural'),
('Sports Day', '2026-12-15', 'Sports Complex', 'Inter-department sports', 'Sports');

INSERT INTO registrations (student_id, event_id) VALUES
(1,1),(2,1),(3,2),(1,3),(4,2),(5,3),(6,1),(7,2),(8,3),(9,4),(10,5);
