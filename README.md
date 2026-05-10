# Secure Online Voting System

A professional, secure, and modern online voting platform built with PHP and MySQL. This system features a modular architecture, robust security measures, and a responsive UI designed for a seamless voting experience.

## 🚀 Features

### Core Functionality
- **Voter Registration & Authentication:** Secure login and registration with password hashing.
- **Voter ID Verification:** Secure file handling for voter identity verification.
- **Dynamic Voting:** Ability to cast votes for multiple candidates and categories.
- **Real-time Results:** Live visualization of voting statistics and results.
- **Voting Windows:** Enforced time frames for voting periods.

### Security
- **SQL Injection Protection:** Fully migrated to PDO prepared statements.
- **CSRF Protection:** Robust defense against cross-site request forgery.
- **Password Hashing:** Industry-standard secure password storage.
- **Race Condition Prevention:** Logic to prevent double-voting or concurrent state issues.

### Administration
- **Admin Dashboard:** Comprehensive statistics and system oversight.
- **User Management:** Tools to manage voters and candidate profiles.
- **Voter Tracking:** Automated status tracking for all registered users.

## 🛠️ Tech Stack
- **Backend:** PHP 8.x (PDO)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (Custom Theme), JavaScript
- **Server:** XAMPP / Apache

## 📂 Project Structure
- `/admin`: Administrative panel and management tools.
- `/user`: Voter-facing pages (login, registration, voting).
- `/config`: Database connections and global configuration.
- `/css`: Unified styling and design tokens.
- `/uploads`: Secure storage for voter ID images and media.
- `schema_updates.sql`: Database migration and structure updates.

## 📥 Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   ```

2. **Database Setup:**
   - Open phpMyAdmin.
   - Create a new database (e.g., `voting_system`).
   - Import `sddproject.sql` first, followed by `schema_updates.sql`.

3. **Configuration:**
   - Navigate to `config/dbconnect.php`.
   - Update the database credentials:
     ```php
     $host = 'localhost';
     $db   = 'voting_system';
     $user = 'root';
     $pass = '';
     ```

4. **Run Application:**
   - Move the project folder to your `htdocs` (XAMPP) or `www` (WAMP) directory.
   - Access via `http://localhost/voting-system`.

## 🎨 Design Aesthetics
The system uses a modern, high-contrast theme with glassmorphism elements and smooth transitions to ensure a premium user experience across all devices.

---
*Developed for secure and transparent digital democracy.*
