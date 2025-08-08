# SSUET CMS Portal

A comprehensive Course Management System (CMS) portal for Sir Syed University of Engineering & Technology (SSUET) that provides separate interfaces for students and faculty members.

## Overview

The SSUET CMS Portal is a web-based application designed to facilitate academic management for both students and faculty. It features role-based access control with separate dashboards for students and faculty members.

## Features

### Student Portal
- Dashboard with academic information
- View enrolled subjects and academic details
- Check attendance records
- View marks, GPA, and CGPA
- Download admit cards and fee vouchers
- Access exam schedules and seating plans
- View academic roadmap
- Change password functionality

### Faculty Portal
- Dashboard with teaching information
- Add and manage student information
- Mark and manage student attendance
- Upload student marks
- Manage CLOs (Course Learning Outcomes) and PLOs (Program Learning Outcomes)
- Create exam schedules
- Manage subject enrollments and academic details
- Change password functionality

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Data Storage**: JSON files (faculty.json, students.json, subjects.json)
- **UI Libraries**: Font Awesome, Select2

## Project Structure

- `/css` - Contains styling files
- `/data` - JSON data files for storing information
- `/faculty` - Faculty portal files and backend APIs
- `/student` - Student portal files and backend APIs
- `/js` - JavaScript files for frontend functionality

## Getting Started

1. Clone the repository
2. Set up a local web server (like XAMPP, WAMP, or MAMP)
3. Place the project files in the web server's document root
4. Access the portal via the web browser at `http://localhost/CMS-Portal`

## Login Information

### Student Login
- Use Roll Number and Password

### Faculty Login
- Use Email Address and Password
- New faculty members can sign up via the "Sign up here" link on the faculty login tab

## Subject Management

The system includes the following subjects:
- CS101 - Operating System
- CS102 - Web Engineering
- CS103 - Data Communication and Computer Networks
- MA101 - Probability and Statistics
- IS101 - Information Security
- CS103L - Data Communication and Computer Networks (Lab)
- CS102L - Web Engineering (Lab)

## License

© 2024 Sir Syed University of Engineering & Technology. All rights reserved.