CREATE DATABASE IF NOT EXISTS studenthub;
USE studenthub;

DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;

CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    course VARCHAR(50)
);

CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(100) NOT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(100)
);

CREATE TABLE registrations (
    reg_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    event_id INT,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

INSERT INTO students (name, email, course) VALUES
('Vraj Patel', 'vraj@example.com', 'BCA'),
('Riya Shah', 'riya@example.com', 'MCA'),
('Arjun Mehta', 'arjun@example.com', 'BCA'),
('Priya Sharma', 'priya@example.com', 'MSc IT'),
('Karan Joshi', 'karan@example.com', 'BCA');

INSERT INTO events (event_name, event_date, location) VALUES
('Tech Fest 2026', '2026-11-15', 'Auditorium'),
('Coding Contest', '2026-11-20', 'Lab 1'),
('Workshop on AI', '2026-12-05', 'Seminar Hall');

INSERT INTO registrations (student_id, event_id) VALUES
(1, 1),
(2, 1),
(3, 2),
(1, 3),
(4, 2),
(5, 3);
