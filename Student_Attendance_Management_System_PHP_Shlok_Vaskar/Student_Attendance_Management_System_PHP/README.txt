STUDENT ATTENDANCE MANAGEMENT SYSTEM
PHP + MongoDB | TYIT Mini Project
Student: Shlok Vaskar | Roll Number: 19062

FEATURES
- Dashboard: total students, present, absent and attendance percentage
- Add and delete student records
- Mark attendance by date (Present / Absent)
- Date-wise attendance report
- Responsive layout
- MongoDB persistence, unique indexes and CSRF token validation

REQUIREMENTS
- XAMPP / WAMP / MAMP with PHP 8.0+
- MongoDB Community Server running locally, or a MongoDB Atlas connection
- PHP MongoDB extension enabled for the PHP version used by Apache and CLI
- Web browser

SETUP WITH XAMPP
1. Copy Student_Attendance_Management_System_PHP into C:\xampp\htdocs\
2. Install MongoDB Community Server and start its MongoDB service. The default local
   connection is mongodb://127.0.0.1:27017. MySQL is not used by this project.
3. Enable the MongoDB PHP extension for XAMPP. Install the matching DLL for your PHP
   version, architecture and thread-safety build, put php_mongodb.dll in C:\xampp\php\ext,
   and add extension=mongodb to C:\xampp\php\php.ini. Restart Apache afterward.
4. Open Command Prompt in the project folder and run:
      C:\xampp\php\php.exe -m
   Confirm that mongodb appears in the module list.
5. Initialize MongoDB indexes and add sample students:
      C:\xampp\php\php.exe setup.php
6. Open http://localhost/Student_Attendance_Management_System_PHP/

MONGODB ATLAS
Set the MONGODB_URI environment variable to your Atlas connection string before starting
Apache. For XAMPP, add MONGODB_URI as a Windows user environment variable, then restart
the XAMPP Control Panel and Apache. Keep database credentials private.

USAGE
1. Open Students and add or delete student records.
2. Open Mark Attendance, select a date, choose Present/Absent, then save.
3. Open Reports and select a date to view the summary.

PROJECT FILES
config.php       MongoDB connection and direct driver helpers
functions.php    Session, escaping, CSRF and flash helpers
index.php        Main application pages and MongoDB operations
setup.php        Creates collection indexes and sample student records
assets/style.css UI styling
README.txt       Setup and usage instructions

NOTE
This is an academic local prototype and has no login/role management. Add authentication,
access controls, backups and institution-approved privacy safeguards before any real deployment.
