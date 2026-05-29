# Grade Management System

A complete web-based Grade Management System built with **PHP**, **MySQL**, **HTML/CSS/JS** (Bootstrap). Supports three roles: **Student**, **Faculty**, and **Admin**.

## Features

### Student Portal
- View grades per subject (internal, practical, final)
- Automatic SGPA/CGPA calculation
- Download transcript as PDF
- View teaching logs (topics taught by faculty)

### Faculty Portal
- Manage students (CRUD)
- Manage courses (CRUD)
- Enroll students into courses
- Enter grades (internal, practical, final) with audit trail
- View class analytics (average, pass rate, charts)
- Teaching log – record topics taught per course

### Admin Portal
- Manage faculty accounts
- View all users (students & faculty)
- Reset any user's password
- View grade change audit log

## Technology Stack
- Backend: PHP (procedural)
- Database: MySQL
- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Libraries: Chart.js, html2pdf.js

## Installation

1. **Clone the repository**  
   ```bash
   git clone https://github.com/sameer76-byte/grade_managment_system.git
   ```

2. **Set up the database**  
   - Create a MySQL database (e.g., `grade_management_system`)
   - Import `setup.sql` from the `database/` folder to create tables
   - (Optional) Import `sample_data.sql` for initial test data (50 students, 24 courses)

3. **Configure database connection**  
   - Copy `config.sample.php` to `config.php`
   - Update database credentials (host, username, password) in `config.php`

4. **Run the application**  
   Place the project folder in your web server's root directory (e.g., `htdocs` for XAMPP).  
   Access `index.php` in your browser.

## Default Login Credentials

| Role     | Username       | Password  |
|----------|----------------|-----------|
| Admin    | admin          | password  |
| Faculty  | dr.sharma      | password  |
| Student  | ayush_sharma   | password  |

## License
MIT

## Author
Sameer Prasad – [GitHub Profile](https://github.com/sameer76-byte)
