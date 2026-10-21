# StudentHub Portal - Comprehensive Full-Stack Academic Portal

> **Practical 15: Final Integration, Testing, Documentation, GitHub Submission, and Local Deployment**
> 
> **Repository:** [https://github.com/VRAJ-710/studenthub-portal](https://github.com/VRAJ-710/studenthub-portal)  
> **Course Outcomes:** CO2, CO4, CO5  
> **Target Deployment:** Local Apache / XAMPP Virtual Host (`http://studenthub.local` or `http://localhost/studenthub-portal/`)

---

## 📖 Project Overview

**StudentHub** is an integrated full-stack web application developed across a semester-long curriculum. It unifies static presentation templates, client-side dynamic scripts, responsive UI layouts, and a multi-layered PHP/MySQL backend architecture. The application supports role-based user authentication, administrative controls, student enrollment and directory management, event planning with file uploads, asynchronous AJAX interactions, and security audit logging.

---

## 🏗️ Architecture & Module Integration

The semester project integrates the following sequential modules into a single, cohesive portal:

| Practical Module | Title / Focus | Key Technologies | Integration Status |
|:---|:---|:---|:---:|
| **Practicals 1–5** | HTML5 Semantic Layout, CSS3 Styling, Flexbox/Grid, Responsive Media Queries | HTML5, CSS3, DOM | ✅ Verified |
| **Practical 6** | Dynamic Client-Side Data Rendering, Search & Filtering | JavaScript, Fetch API | ✅ Verified |
| **Practical 7** | Server-Side Form Handling & File Persistence | PHP, CSV (`fputcsv`), JSON | ✅ Verified |
| **Practical 8** | Relational Database Architecture & MySQL Connection | PHP MySQLi, MySQL | ✅ Verified |
| **Practical 9** | Secure User Registration, Password Hashing & Duplicate Validation | `password_hash()` (Bcrypt), Prepared Statements | ✅ Verified |
| **Practical 10** | Session Management & Role-Based Access Control (RBAC) | `$_SESSION`, Timeout (1800s), Regenerate ID | ✅ Verified |
| **Practical 11** | Dynamic Data Grid, Search, Course Filter, Pagination & CSV Export | Parameterized SQL, Content-Disposition CSV | ✅ Verified |
| **Practical 12** | Secure Multipart File Upload (Event Poster / Profile Picture) | MIME verification, File Size Limit (2MB), Safe Uniqid Rename | ✅ Verified |
| **Practical 13** | Asynchronous RESTful CRUD Operations | Fetch API, JSON API Endpoint, No-Reload DOM updates | ✅ Verified |
| **Practical 14** | Admin Dashboard, Metric Aggregations & Audit Log | RBAC Protected Routes, Audit Trail, SQL Joins | ✅ Verified |
| **Practical 15** | Final System Integration, Automated Diagnostics, Local Deployment & Documentation | Full-Stack Integration, Virtual Host, End-to-End Suite | ✅ Complete |

---

## ⚙️ Technology Stack

- **Frontend:** HTML5, CSS3 (Custom Responsive System + Flexbox/Grid), Modern Vanilla JavaScript (ES6+ Fetch API, DOM manipulation).
- **Backend:** PHP 8.x with strict type validation, session state management, and defensive error handling.
- **Database:** MySQL 8.x / MariaDB running InnoDB storage engine with foreign key constraints.
- **Web Server:** Apache HTTP Server 2.4 (via XAMPP).
- **Version Control:** Git & GitHub.

---

## 🚀 Local Deployment Guide (XAMPP)

### Prerequisites
- XAMPP installed on `C:\xampp\` with Apache and MySQL enabled.
- Web browser (Chrome, Edge, or Firefox).

### 1. Database Setup
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Navigate to `http://localhost/phpmyadmin/` or open MySQL CLI.
3. Import the consolidated database schema and seed data located at:
   ```bash
   mysql -u root -p < practical15/database.sql
   ```
   *(Or run the SQL statements inside `practical9/database.sql` / `practical15/database.sql` in phpMyAdmin).*

### 2. Standard Local Access
Open any modern browser and visit:
```
http://localhost/studenthub-portal/
```
From the homepage, select **Practical 15 - Final Integration Portal** (`practical15/index.php`).

### 3. Apache Virtual Host Deployment (Optional / Production-like)
To host under a clean local domain `http://studenthub.local/`:
1. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf` and append:
   ```apache
   <VirtualHost *:80>
       ServerAdmin admin@studenthub.local
       DocumentRoot "C:/xampp/htdocs/studenthub-portal"
       ServerName studenthub.local
       ServerAlias www.studenthub.local
       <Directory "C:/xampp/htdocs/studenthub-portal">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       ErrorLog "logs/studenthub-error.log"
       CustomLog "logs/studenthub-access.log" common
   </VirtualHost>
   ```
2. Open Notepad as **Administrator** and edit `C:\Windows\System32\drivers\etc\hosts`:
   ```
   127.0.0.1    studenthub.local
   127.0.0.1    www.studenthub.local
   ```
3. Restart Apache from the XAMPP Control Panel.
4. Access the portal directly at `http://studenthub.local/`.

---

## 🔐 Default Test Accounts

| Username | Password | Role | Access Level |
|:---|:---|:---|:---|
| `admin` | `password` | **Admin** | Full administrative rights, Event creation with poster upload, Student directory management, Audit logs, System Diagnostics |
| `vraj` | `password` | **Student** | Student dashboard, Event browsing, One-click AJAX Event Registration, Profile view |
| `riya` | `password` | **Student** | Student dashboard & Event registration |

---

## 🛡️ Security & Accessibility Compliance

- **SQL Injection Prevention:** 100% of dynamic queries use prepared statements with parameter binding (`$stmt->bind_param()`).
- **Cross-Site Scripting (XSS) Defense:** All user inputs and database outputs are escaped using `htmlspecialchars()` with UTF-8 encoding.
- **Password Security:** Salted hashes generated via PHP's `password_hash($pwd, PASSWORD_DEFAULT)` employing Bcrypt. Plaintext passwords are never stored.
- **Session Protection:** Session fixation mitigated with `session_regenerate_id(true)`. Automatic 30-minute inactivity timeout (`$timeout = 1800`) enforces re-authentication.
- **File Upload Security:** Multi-layer validation verifies real MIME types (`image/jpeg`, `image/png`), enforces a strict 2MB limit, and sanitizes filenames using `uniqid('poster_')`.
- **Accessibility (WCAG 2.1 AA):** High-contrast color palette (`#1f4e78`), visible focus indicators, semantic HTML5 landmarks (`<header>`, `<nav>`, `<main>`, `<aside>`), and ARIA live regions for AJAX feedback.

---

## 🧪 Integration Testing & Verification

1. **Navigation Verification:** Verified all cross-page links, relative asset paths (CSS/JS), and back-links across all 15 practical components.
2. **Session & RBAC Testing:** Verified unauthorized access attempts to `admin_dashboard.php` are intercepted and redirected to `student_dashboard.php` or login.
3. **AJAX Stress Testing:** Verified asynchronous event registration and cancellation update the database and user interface simultaneously without full page reloads.
4. **Data Integrity Testing:** Verified relational cascade deletes between `users`, `students`, `events`, and `registrations`.

---

## 📂 Repository Directory Structure

```
studenthub-portal/
├── index.html                   # Master entry portal linking all static & dynamic practicals
├── README.md                    # Project documentation & deployment manual
├── 25dce027-files/              # Academic practical documentation PDFs (P1 - P15)
├── css/                         # Global styles & responsive rules
├── java/                        # Client-side scripts & form validation
├── pages/                       # Static portal pages (Login, Profile, Course, etc.)
├── practical7/                  # Practical 7: CSV & JSON storage pipeline
├── practical8/                  # Practical 8: Database configuration & connection
├── practical9/                  # Practical 9: User registration with Bcrypt hashing
├── practical10/                 # Practical 10: Session management & RBAC
├── practical11/                 # Practical 11: Data grid, search, filter, pagination & CSV export
├── practical12/                 # Practical 12: Event management with secure poster upload
├── practical13/                 # Practical 13: Asynchronous Fetch CRUD API
├── practical14/                 # Practical 14: Role-based Admin Dashboard & Audit Log
└── practical15/                 # Practical 15: Master Integration, Diagnostics & Deployment
    ├── index.php                # Unified master portal dashboard
    ├── api.php                  # Asynchronous AJAX endpoints
    ├── database.sql             # Consolidated semester database schema & seed data
    └── virtual-host.conf        # Apache Virtual Host configuration template
```

---
*Developed as part of the Web Development Frameworks laboratory curriculum.*
