// Main JavaScript for SSUET Portal
class SSUETPortal {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Tab switching
        const tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Form submissions
        document.getElementById('studentForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleStudentLogin();
        });

        document.getElementById('facultyForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFacultyLogin();
        });
    }

    switchTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');

        // Update forms
        document.querySelectorAll('.login-form').forEach(form => {
            form.classList.remove('active');
        });
        document.getElementById(`${tab}-login`).classList.add('active');
    }

    async handleStudentLogin() {
        const rollNumber = document.getElementById('studentRoll').value;
        const password = document.getElementById('studentPassword').value;

        // Use PHP backend for student authentication
        try {
            const res = await fetch('student/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ rollNumber, password })
            });
            const data = await res.json();
            if (data.success) {
                this.currentUser = { ...data.student, role: 'student' };
                // Store student roll number in localStorage for session management
                localStorage.setItem('currentStudentRoll', data.student.rollNumber);
                this.showSuccessMessage('Login successful! Redirecting...');
                setTimeout(() => {
                    window.location.href = 'student/dashboard.html';
                }, 1500);
            } else {
                this.showErrorMessage(data.message || 'Invalid roll number or password');
            }
        } catch (err) {
            this.showErrorMessage('Server error. Please try again.');
        }
    }

    async handleFacultyLogin() {
        const email = document.getElementById('facultyEmail').value;
        const password = document.getElementById('facultyPassword').value;

        try {
            const res = await fetch('faculty/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            const data = await res.json();
            if (data.success) {
                this.currentUser = { ...data.faculty, role: 'faculty' };
                localStorage.setItem('currentFacultyEmail', email);
                this.showSuccessMessage('Login successful! Redirecting...');
                setTimeout(() => {
                    window.location.href = 'faculty/dashboard.html';
                }, 1500);
            } else {
                this.showErrorMessage(data.message || 'Invalid email or password');
            }
        } catch (err) {
            this.showErrorMessage('Server error. Please try again.');
        }
    }

    showSuccessMessage(message) {
        this.showMessage(message, 'success');
    }

    showErrorMessage(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());

        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}-message`;
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';

        // Insert message before the form
        const activeForm = document.querySelector('.login-form.active');
        activeForm.insertBefore(messageDiv, activeForm.firstChild);

        // Auto remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Initialize the portal when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SSUETPortal();
});

// Global utility functions
window.SSUETUtils = {
    formatDate: (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    calculateGPA: (marks) => {
        const gradePoints = {
            'A+': 4.0, 'A': 4.0, 'A-': 3.7,
            'B+': 3.3, 'B': 3.0, 'B-': 2.7,
            'C+': 2.3, 'C': 2.0, 'C-': 1.7,
            'D+': 1.3, 'D': 1.0, 'F': 0.0
        };

        let totalPoints = 0;
        let totalCredits = 0;

        Object.values(marks).forEach(course => {
            const gradePoint = gradePoints[course.grade] || 0;
            totalPoints += gradePoint * 3; // Assuming 3 credits per course
            totalCredits += 3;
        });

        return totalCredits > 0 ? (totalPoints / totalCredits).toFixed(2) : 0;
    },

    getAttendanceStatus: (percentage) => {
        return percentage >= 75 ? "Good Standing" : "SOA - Short of Attendance";
    },

    generateRandomId: () => {
        return Math.random().toString(36).substr(2, 9);
    }
};

// Password toggle functionality
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggleBtn = input.parentElement.querySelector('.password-toggle i');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggleBtn.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        toggleBtn.className = 'fas fa-eye';
    }
} 