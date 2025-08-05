// Student Dashboard JS
class StudentDashboard {
    constructor() {
        this.sections = document.querySelectorAll('.section');
        this.sidebarLinks = document.querySelectorAll('.sidebar nav ul li');
        this.student = null;
        this.init();
    }

    init() {
        this.loadStudentFromSession();
        this.setupSidebarNavigation();
        this.setupChangePasswordForm();
        // Add more setup functions for attendance, marks, admit card, fee voucher, roadmap, exam schedule
    }

    async loadStudentFromSession() {
        const studentRoll = localStorage.getItem('currentStudentRoll');
        if (studentRoll) {
            try {
                const response = await fetch('get_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ rollNumber: studentRoll })
                });
                const result = await response.json();
                if (result.success) {
                    this.student = result.student;
                    this.updateStudentName();
                    this.renderStudentInfo();
                    this.renderStudentSubjects();
                    this.renderRoadmap();
                } else {
                    this.updateStudentName();
                }
            } catch (error) {
                console.error('Error loading student info:', error);
                this.updateStudentName();
            }
        }
    }

    updateStudentName() {
        if (this.student) {
            const header = document.getElementById('welcomeHeader');
            if (header) {
                header.innerHTML = `Welcome, <b>${this.student.name}</b>!`;
            }
        }
    }

    renderStudentSubjects() {
        const container = document.getElementById('studentSubjectsContainer');
        if (!container) return;

        const subjects = this.student.subjects || [];

        if (subjects.length === 0) {
            container.innerHTML = '<p>You are not enrolled in any subjects yet.</p>';
            return;
        }

        container.innerHTML = ''; // Clear previous content
        subjects.forEach(subject => {
            const card = document.createElement('div');
            card.className = 'student-subject-card';
            card.innerHTML = `
                <h4>${this.escapeHtml(subject.name)}</h4>
                <div class="subject-detail">
                    <strong>Attendance:</strong>
                    <span>${subject.attendance || 0}%</span>
                </div>
                <div class="subject-detail">
                    <strong>Mid Term Marks:</strong>
                    <span>${subject.marks.mid_term || 'N/A'}</span>
                </div>
                <div class="subject-detail">
                    <strong>Final Exam Marks:</strong>
                    <span>${subject.marks.final_exam || 'N/A'}</span>
                </div>
            `;
            container.appendChild(card);
        });
    }

    renderRoadmap() {
        const container = document.getElementById('roadmap-section');
        if (container) {
            // Placeholder: This would be built out with real roadmap data
            container.innerHTML = `
                <h2>Academic Roadmap</h2>
                <p>Academic roadmap feature is under development.</p>
            `;
        }
    }

    renderStudentInfo() {
        const infoContainer = document.getElementById('dashboard-section');
        if (infoContainer && this.student) {
            const infoGrid = document.createElement('div');
            infoGrid.className = 'info-grid';
            infoGrid.innerHTML = `
                <div class="info-item"><strong>Roll Number:</strong><span>${this.student.rollNumber}</span></div>
                <div class="info-item"><strong>Email:</strong><span>${this.student.email}</span></div>
                <div class="info-item"><strong>Department:</strong><span>${this.student.department}</span></div>
                <div class="info-item"><strong>Semester:</strong><span>${this.student.semester}</span></div>
                <div class="info-item"><strong>CGPA:</strong><span>${this.student.cgpa || 'N/A'}</span></div>
                <div class="info-item"><strong>Status:</strong><span class="status-${this.student.status}">${this.student.status}</span></div>
            `;
            // Prepend it after the welcome message
            infoContainer.appendChild(infoGrid);
        }
    }

    setupSidebarNavigation() {
        this.sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.sidebarLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                this.sections.forEach(section => section.classList.remove('active'));
                const sectionId = link.dataset.section + '-section';
                const section = document.getElementById(sectionId);
                if (section) section.classList.add('active');
                if (link.dataset.section === 'logout') {
                    this.logout();
                }
            });
        });
    }

    setupChangePasswordForm() {
        const form = document.getElementById('studentChangePasswordForm');
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const current = document.getElementById('studentCurrentPassword').value;
            const newPass = document.getElementById('studentNewPassword').value;
            
            // Use PHP backend for password change
            this.changeStudentPassword(current, newPass);
        });
    }

    async changeStudentPassword(currentPassword, newPassword) {
        try {
            const response = await fetch('change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    rollNumber: this.student.rollNumber,
                    currentPassword: currentPassword,
                    newPassword: newPassword
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.showMessage('Password changed successfully!', 'success');
                document.getElementById('studentChangePasswordForm').reset();
            } else {
                this.showMessage(result.message, 'error');
            }
        } catch (error) {
            this.showMessage('Error changing password. Please try again.', 'error');
        }
    }

    showMessage(msg, type) {
        const div = document.createElement('div');
        div.className = type === 'success' ? 'success-message' : 'error-message';
        div.textContent = msg;
        div.style.display = 'block';
        document.querySelector('.main-content').prepend(div);
        setTimeout(() => div.remove(), 4000);
    }

    logout() {
        // Clear student session
        localStorage.removeItem('currentStudentRoll');
        window.location.href = '../index.html';
    }

    escapeHtml(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
}
document.addEventListener('DOMContentLoaded', () => new StudentDashboard());

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