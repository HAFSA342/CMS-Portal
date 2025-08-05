// Faculty Dashboard JS
class FacultyDashboard {
    constructor() {
        this.sections = document.querySelectorAll('.section');
        this.sidebarLinks = document.querySelectorAll('.sidebar nav ul li');
        this.faculty = null;
        this.currentStudent = null;
        this.assignedSubjects = [];
        this.init();
    }

    init() {
        this.loadFacultyFromSession();
        this.setupSidebarNavigation();
        this.setupChangePasswordForm();
        this.setupSubjectManagement();
        this.setupStudentManagementNew(); // New student management system
    }

    updateFacultyName() {
        if (this.faculty) {
            const header = document.getElementById('welcomeHeader');
            if (header) {
                header.innerHTML = `Welcome, <b>${this.faculty.name}</b>!`;
            }
        }
    }

    async loadFacultyFromSession() {
        const facultyEmail = localStorage.getItem('currentFacultyEmail');
        
        if (facultyEmail) {
            try {
                const response = await fetch('get_faculty_subjects.php?email=' + encodeURIComponent(facultyEmail));
                const result = await response.json();
                
                if (result.success) {
                    this.faculty = result.faculty;
                    this.assignedSubjects = result.assigned_subjects;
                    this.updateFacultyName();
                } else {
                    this.showMessage('Failed to load faculty information: ' + result.message, 'error');
                }
            } catch (error) {
                this.showMessage('Error loading faculty information: ' + error.message, 'error');
            }
        } else {
            this.showMessage('No faculty session found. Please login again.', 'error');
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 2000);
        }
    }

    setupSidebarNavigation() {
        this.sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.sidebarLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                this.sections.forEach(section => section.classList.remove('active'));
                const sectionId = link.dataset.section + '-section';
                document.getElementById(sectionId)?.classList.add('active');
                if (link.dataset.section === 'logout') this.logout();
            });
        });
    }

    setupChangePasswordForm() {
        document.getElementById('facultyChangePasswordForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.showMessage('Password change functionality coming soon!', 'info');
        });
    }

    showMessage(msg, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}-message`;
        messageDiv.textContent = msg;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            max-width: 300px;
            word-wrap: break-word;
        `;
        
        switch (type) {
            case 'success':
                messageDiv.style.backgroundColor = '#28a745';
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#dc3545';
                break;
            case 'info':
                messageDiv.style.backgroundColor = '#17a2b8';
                break;
            default:
                messageDiv.style.backgroundColor = '#6c757d';
        }
        
        document.body.appendChild(messageDiv);
        setTimeout(() => messageDiv.remove(), 5000);
    }

    logout() {
        localStorage.removeItem('currentFacultyEmail');
        window.location.href = '../index.html';
    }

    // Removed: setupAddStudentForm, setupStudentManagement, setupEditModal, updateStudent, deleteStudent, editStudent, filterStudents, loadStudentList, getStudentListData, loadStudentsIntoSelector, handleStudentSelection, loadStudentEnrollments, enrollSelectedSubject, renderEnrolledSubjects, addCardEventListeners, calculateGrade, saveAcademicData, isFieldInDataType, escapeHtml

    // --- Subject Management ---

    setupSubjectManagement() {
        // Only keep subject management logic. Student management will be re-implemented.
        this.masterSubjectSelector = document.getElementById('masterSubjectSelector');
        this.enrollSubjectBtn = document.getElementById('enrollSubjectBtn');
        this.studentSubjectDetails = document.getElementById('studentSubjectDetails');
        this.managingStudentName = document.getElementById('managingStudentName');
        this.enrolledSubjectsContainer = document.getElementById('enrolledSubjectsContainer');
        // New student management system will be added here.
    }

    async loadAssignedSubjects() {
        if (!this.assignedSubjects || this.assignedSubjects.length === 0) {
            this.masterSubjectSelector.innerHTML = '<option value="">No subjects assigned to you</option>';
            return;
        }

        // Fetch all subjects
        const response = await fetch('../data/subjects.json');
        const allSubjects = await response.json();

        this.masterSubjectSelector.innerHTML = '<option value="">Select a subject to enroll</option>';
        this.assignedSubjects.forEach(subjId => {
            const subj = allSubjects.find(s => s.id === subjId);
            if (subj) {
                this.masterSubjectSelector.innerHTML += `<option value="${subj.id}">${subj.id} - ${subj.name}</option>`;
            }
        });
    }

    async loadStudentsIntoSelector() {
        try {
            const students = await this.getStudentListData();
            this.studentSelector.innerHTML = '<option value="">Select a student</option>';
            students.forEach(student => {
                this.studentSelector.innerHTML += `<option value="${student.rollNumber}">${student.name} (${student.rollNumber})</option>`;
            });
        } catch (error) {
            console.error('Error loading students into selector:', error);
        }
    }

    handleStudentSelection() {
        const selectedRoll = this.studentSelector.value;
        if (selectedRoll) {
            this.currentStudent = selectedRoll;
            this.studentSubjectDetails.classList.remove('hidden');
            this.loadStudentEnrollments();
        } else {
            this.currentStudent = null;
            this.studentSubjectDetails.classList.add('hidden');
        }
    }

    async loadStudentEnrollments() {
        if (!this.currentStudent) return;

        try {
            const facultyEmail = localStorage.getItem('currentFacultyEmail');
            const response = await fetch(`get_faculty_enrollments.php?email=${encodeURIComponent(facultyEmail)}`);
            const result = await response.json();
            
            if (result.success) {
                // Filter enrollments for current student
                const studentEnrollments = result.enrollments.filter(enrollment => 
                    enrollment.enrollment.student_roll === this.currentStudent
                );
                
                this.renderEnrolledSubjects(studentEnrollments);
                
                // Update student name
                if (studentEnrollments.length > 0 && studentEnrollments[0].student) {
                    this.managingStudentName.textContent = `Managing: ${studentEnrollments[0].student.name}`;
                } else {
                    // Get student name from students list
                    const students = await this.getStudentListData();
                    const student = students.find(s => s.rollNumber === this.currentStudent);
                    this.managingStudentName.textContent = student ? `Managing: ${student.name}` : `Managing: ${this.currentStudent}`;
                }
            } else {
                this.showMessage('Failed to load student enrollments', 'error');
            }
        } catch (error) {
            console.error('Error loading student enrollments:', error);
            this.showMessage('Error loading student enrollments', 'error');
        }
    }

    async enrollSelectedSubject() {
        if (!this.currentStudent) {
            this.showMessage('Please select a student first', 'error');
            return;
        }

        const subjectId = this.masterSubjectSelector.value;
        if (!subjectId) {
            this.showMessage('Please select a subject to enroll', 'error');
            return;
        }

        const facultyEmail = localStorage.getItem('currentFacultyEmail');
        if (!facultyEmail) {
            this.showMessage('Faculty session not found', 'error');
            return;
        }

        this.enrollSubjectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enrolling...';
        this.enrollSubjectBtn.disabled = true;

        try {
            const response = await fetch('enroll_student_subject.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_roll: this.currentStudent,
                    subject_id: subjectId,
                    faculty_email: facultyEmail
                })
            });
            const result = await response.json();
            
            if (result.success) {
                this.showMessage('Student enrolled successfully!', 'success');
                this.masterSubjectSelector.value = '';
                this.loadStudentEnrollments();
            } else {
                this.showMessage(result.message || 'Failed to enroll student', 'error');
            }
        } catch (error) {
            this.showMessage('Network error. Please try again.', 'error');
        } finally {
            this.enrollSubjectBtn.innerHTML = 'Enroll Student';
            this.enrollSubjectBtn.disabled = false;
        }
    }

    renderEnrolledSubjects(enrollments) {
        if (!enrollments || enrollments.length === 0) {
            this.enrolledSubjectsContainer.innerHTML = '<p class="no-data">No subjects enrolled yet.</p>';
            return;
        }

        let html = '';
        enrollments.forEach(enrollmentData => {
            const enrollment = enrollmentData.enrollment;
            const subject = enrollmentData.subject;
            
            html += `
                <div class="enrolled-subject-card" data-subject-id="${enrollment.subject_id}">
                    <div class="subject-card-header">
                        <h5>${subject ? subject.name : enrollment.subject_id}</h5>
                        <span class="subject-id">${enrollment.subject_id}</span>
                    </div>
                    
                    <div class="subject-tabs">
                        <button class="subject-tab active" data-tab="attendance">Attendance</button>
                        <button class="subject-tab" data-tab="marks">Marks</button>
                        <button class="subject-tab" data-tab="clos">CLOs</button>
                        <button class="subject-tab" data-tab="plos">PLOs</button>
                    </div>
                    
                    <div class="subject-tab-content active" data-tab="attendance">
                        <div class="form-grid">
                            <div>
                                <label>Total Classes</label>
                                <input type="number" value="${enrollment.attendance.total_classes}" min="0" data-field="total_classes">
                            </div>
                            <div>
                                <label>Attended Classes</label>
                                <input type="number" value="${enrollment.attendance.attended_classes}" min="0" data-field="attended_classes">
                            </div>
                            <div>
                                <label>Attendance %</label>
                                <input type="number" value="${enrollment.attendance.percentage}" min="0" max="100" data-field="percentage" readonly>
                            </div>
                        </div>
                        <button class="action-btn save-btn" onclick="facultyDashboard.saveAcademicData(this, '${enrollment.subject_id}', 'attendance')">
                            Save Attendance
                        </button>
                    </div>
                    
                    <div class="subject-tab-content" data-tab="marks">
                        <div class="form-grid">
                            <div>
                                <label>Midterm</label>
                                <input type="number" value="${enrollment.marks.midterm}" min="0" max="100" data-field="midterm">
                            </div>
                            <div>
                                <label>Final</label>
                                <input type="number" value="${enrollment.marks.final}" min="0" max="100" data-field="final">
                            </div>
                            <div>
                                <label>Assignments</label>
                                <input type="number" value="${enrollment.marks.assignments}" min="0" max="100" data-field="assignments">
                            </div>
                            <div>
                                <label>Total</label>
                                <input type="number" value="${enrollment.marks.total}" min="0" max="100" data-field="total" readonly>
                            </div>
                            <div>
                                <label>Grade</label>
                                <input type="text" value="${enrollment.marks.grade}" data-field="grade" readonly>
                            </div>
                        </div>
                        <button class="action-btn save-btn" onclick="facultyDashboard.saveAcademicData(this, '${enrollment.subject_id}', 'marks')">
                            Save Marks
                        </button>
                    </div>
                    
                    <div class="subject-tab-content" data-tab="clos">
                        <div class="form-grid">
                            <div><label>CLO 1</label><input type="number" value="${enrollment.clos.clo1}" min="0" max="100" data-field="clo1"></div>
                            <div><label>CLO 2</label><input type="number" value="${enrollment.clos.clo2}" min="0" max="100" data-field="clo2"></div>
                            <div><label>CLO 3</label><input type="number" value="${enrollment.clos.clo3}" min="0" max="100" data-field="clo3"></div>
                            <div><label>CLO 4</label><input type="number" value="${enrollment.clos.clo4}" min="0" max="100" data-field="clo4"></div>
                        </div>
                        <button class="action-btn save-btn" onclick="facultyDashboard.saveAcademicData(this, '${enrollment.subject_id}', 'clos')">
                            Save CLOs
                        </button>
                    </div>
                    
                    <div class="subject-tab-content" data-tab="plos">
                        <div class="form-grid">
                            <div><label>PLO 1</label><input type="number" value="${enrollment.plos.plo1}" min="0" max="100" data-field="plo1"></div>
                            <div><label>PLO 2</label><input type="number" value="${enrollment.plos.plo2}" min="0" max="100" data-field="plo2"></div>
                            <div><label>PLO 3</label><input type="number" value="${enrollment.plos.plo3}" min="0" max="100" data-field="plo3"></div>
                            <div><label>PLO 4</label><input type="number" value="${enrollment.plos.plo4}" min="0" max="100" data-field="plo4"></div>
                        </div>
                        <button class="action-btn save-btn" onclick="facultyDashboard.saveAcademicData(this, '${enrollment.subject_id}', 'plos')">
                            Save PLOs
                        </button>
                    </div>
                </div>
            `;
        });

        this.enrolledSubjectsContainer.innerHTML = html;
        this.addCardEventListeners();
    }

    addCardEventListeners() {
        // Tab switching
        document.querySelectorAll('.subject-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const card = tab.closest('.enrolled-subject-card');
                const tabName = tab.dataset.tab;
                
                // Update active tab
                card.querySelectorAll('.subject-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Update active content
                card.querySelectorAll('.subject-tab-content').forEach(content => content.classList.remove('active'));
                card.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            });
        });

        // Auto-calculate attendance percentage
        document.querySelectorAll('input[data-field="total_classes"], input[data-field="attended_classes"]').forEach(input => {
            input.addEventListener('input', () => {
                const card = input.closest('.enrolled-subject-card');
                const totalClasses = parseInt(card.querySelector('input[data-field="total_classes"]').value) || 0;
                const attendedClasses = parseInt(card.querySelector('input[data-field="attended_classes"]').value) || 0;
                const percentage = totalClasses > 0 ? Math.round((attendedClasses / totalClasses) * 100) : 0;
                card.querySelector('input[data-field="percentage"]').value = percentage;
            });
        });

        // Auto-calculate total marks
        document.querySelectorAll('input[data-field="midterm"], input[data-field="final"], input[data-field="assignments"]').forEach(input => {
            input.addEventListener('input', () => {
                const card = input.closest('.enrolled-subject-card');
                const midterm = parseInt(card.querySelector('input[data-field="midterm"]').value) || 0;
                const final = parseInt(card.querySelector('input[data-field="final"]').value) || 0;
                const assignments = parseInt(card.querySelector('input[data-field="assignments"]').value) || 0;
                const total = Math.round((midterm * 0.3) + (final * 0.5) + (assignments * 0.2));
                
                const totalInput = card.querySelector('input[data-field="total"]');
                totalInput.value = total;
                
                // Calculate grade
                const gradeInput = card.querySelector('input[data-field="grade"]');
                gradeInput.value = this.calculateGrade(total);
            });
        });
    }

    calculateGrade(total) {
        if (total >= 90) return 'A+';
        if (total >= 85) return 'A';
        if (total >= 80) return 'A-';
        if (total >= 75) return 'B+';
        if (total >= 70) return 'B';
        if (total >= 65) return 'B-';
        if (total >= 60) return 'C+';
        if (total >= 55) return 'C';
        if (total >= 50) return 'C-';
        if (total >= 45) return 'D+';
        if (total >= 40) return 'D';
        return 'F';
    }

    async saveAcademicData(button, subjectId, dataType) {
        const card = button.closest('.enrolled-subject-card');
        const data = {};
        
        card.querySelectorAll(`input[data-field]`).forEach(input => {
            const field = input.dataset.field;
            if (this.isFieldInDataType(field, dataType)) {
                data[field] = parseInt(input.value) || 0;
            }
        });

        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        button.disabled = true;

        try {
            const response = await fetch('update_academics.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_roll: this.currentStudent,
                    subject_id: subjectId,
                    faculty_email: localStorage.getItem('currentFacultyEmail'),
                    data_type: dataType,
                    data: data
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.showMessage(`${dataType.charAt(0).toUpperCase() + dataType.slice(1)} updated successfully!`, 'success');
            } else {
                this.showMessage(result.message || 'Failed to update data', 'error');
            }
        } catch (error) {
            this.showMessage('Network error. Please try again.', 'error');
        } finally {
            button.innerHTML = `Save ${dataType.charAt(0).toUpperCase() + dataType.slice(1)}`;
            button.disabled = false;
        }
    }

    isFieldInDataType(field, dataType) {
        const fieldMappings = {
            attendance: ['total_classes', 'attended_classes', 'percentage'],
            marks: ['midterm', 'final', 'assignments', 'total', 'grade'],
            clos: ['clo1', 'clo2', 'clo3', 'clo4'],
            plos: ['plo1', 'plo2', 'plo3', 'plo4']
        };
        return fieldMappings[dataType]?.includes(field) || false;
    }

    // --- New Student Management System ---
    setupStudentManagementNew() {
        this.studentTableDiv = document.getElementById('studentsTable');
        this.studentSearchInput = document.getElementById('studentSearch');
        this.addStudentBtn = document.getElementById('addStudentBtn');
        this.addStudentModal = document.getElementById('addStudentModal');
        this.editStudentModal = document.getElementById('editStudentModal');
        this.addStudentForm = document.getElementById('addStudentForm');
        this.editStudentForm = document.getElementById('editStudentForm');

        if (this.studentSearchInput) {
            this.studentSearchInput.addEventListener('input', (e) => this.filterStudentTable(e.target.value));
        }
        if (this.addStudentBtn) {
            this.addStudentBtn.addEventListener('click', () => this.showAddStudentModal());
        }
        if (this.addStudentForm) {
            this.addStudentForm.addEventListener('submit', (e) => this.handleAddStudent(e));
        }
        if (this.editStudentForm) {
            this.editStudentForm.addEventListener('submit', (e) => this.handleEditStudent(e));
        }
        this.loadStudentListNew();
    }

    async loadStudentListNew() {
        if (!this.studentTableDiv) return;
        this.studentTableDiv.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
        try {
            const res = await fetch('get_students.php');
            const data = await res.json();
            if (data.success && Array.isArray(data.students)) {
                this.renderStudentTable(data.students);
                this._allStudents = data.students;
            } else {
                this.studentTableDiv.innerHTML = '<p class="no-data">No students found.</p>';
            }
        } catch (err) {
            this.studentTableDiv.innerHTML = '<p class="error">Error loading students.</p>';
        }
    }

    renderStudentTable(students) {
        let html = `<button id="addStudentBtn" class="action-btn">Add Student</button>`;
        html += `<input type="text" id="studentSearch" placeholder="Search students..." style="margin-left:10px;">`;
        html += '<table class="students-table"><thead><tr><th>Name</th><th>Roll Number</th><th>Email</th><th>Phone</th><th>Department</th><th>Semester</th><th>Actions</th></tr></thead><tbody>';
        for (const s of students) {
            html += `<tr>
                <td>${this.escapeHtml(s.name)}</td>
                <td>${this.escapeHtml(s.rollNumber)}</td>
                <td>${this.escapeHtml(s.email)}</td>
                <td>${this.escapeHtml(s.phone)}</td>
                <td>${this.escapeHtml(s.department)}</td>
                <td>${s.semester}</td>
                <td>
                    <button class="btn-edit" data-id="${s.id}">Edit</button>
                    <button class="btn-delete" data-id="${s.id}" data-name="${this.escapeHtml(s.name)}">Delete</button>
                </td>
            </tr>`;
        }
        html += '</tbody></table>';
        this.studentTableDiv.innerHTML = html;
        // Re-bind events
        this.studentTableDiv.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => this.showEditStudentModal(btn.dataset.id));
        });
        this.studentTableDiv.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleDeleteStudent(btn.dataset.id, btn.dataset.name));
        });
        // Re-bind add/search
        document.getElementById('addStudentBtn').addEventListener('click', () => this.showAddStudentModal());
        document.getElementById('studentSearch').addEventListener('input', (e) => this.filterStudentTable(e.target.value));
    }

    filterStudentTable(searchTerm) {
        if (!this._allStudents) return;
        const filtered = this._allStudents.filter(s => {
            return (
                s.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                s.rollNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                s.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                s.phone.toLowerCase().includes(searchTerm.toLowerCase()) ||
                s.department.toLowerCase().includes(searchTerm.toLowerCase())
            );
        });
        this.renderStudentTable(filtered);
    }

    showAddStudentModal() {
        if (!this.addStudentModal) return;
        this.addStudentModal.style.display = 'block';
        this.addStudentForm.reset();
    }
    showEditStudentModal(studentId) {
        if (!this.editStudentModal) return;
        const student = this._allStudents.find(s => s.id == studentId);
        if (!student) return;
        this.editStudentModal.style.display = 'block';
        // Fill form fields
        this.editStudentForm.elements['id'].value = student.id;
        this.editStudentForm.elements['name'].value = student.name;
        this.editStudentForm.elements['rollNumber'].value = student.rollNumber;
        this.editStudentForm.elements['email'].value = student.email;
        this.editStudentForm.elements['phone'].value = student.phone;
        this.editStudentForm.elements['department'].value = student.department;
        this.editStudentForm.elements['semester'].value = student.semester;
    }
    async handleAddStudent(e) {
        e.preventDefault();
        const form = this.addStudentForm;
        const student = {
            name: form.elements['name'].value.trim(),
            rollNumber: form.elements['rollNumber'].value.trim(),
            email: form.elements['email'].value.trim(),
            phone: form.elements['phone'].value.trim(),
            department: form.elements['department'].value.trim(),
            password: form.elements['password'].value,
            semester: parseInt(form.elements['semester'].value)
        };
        try {
            const res = await fetch('add_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(student)
            });
            const data = await res.json();
            if (data.success) {
                this.showMessage('Student added successfully!', 'success');
                this.addStudentModal.style.display = 'none';
                this.loadStudentListNew();
            } else {
                this.showMessage(data.message || 'Failed to add student', 'error');
            }
        } catch (err) {
            this.showMessage('Network error. Please try again.', 'error');
        }
    }
    async handleEditStudent(e) {
        e.preventDefault();
        const form = this.editStudentForm;
        const student = {
            id: form.elements['id'].value,
            name: form.elements['name'].value.trim(),
            rollNumber: form.elements['rollNumber'].value.trim(),
            email: form.elements['email'].value.trim(),
            phone: form.elements['phone'].value.trim(),
            department: form.elements['department'].value.trim(),
            semester: parseInt(form.elements['semester'].value)
        };
        try {
            const res = await fetch('update_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(student)
            });
            const data = await res.json();
            if (data.success) {
                this.showMessage('Student updated successfully!', 'success');
                this.editStudentModal.style.display = 'none';
                this.loadStudentListNew();
            } else {
                this.showMessage(data.message || 'Failed to update student', 'error');
            }
        } catch (err) {
            this.showMessage('Network error. Please try again.', 'error');
        }
    }
    async handleDeleteStudent(studentId, studentName) {
        if (!confirm(`Are you sure you want to delete ${studentName}?`)) return;
        try {
            const res = await fetch('delete_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: studentId })
            });
            const data = await res.json();
            if (data.success) {
                this.showMessage('Student deleted successfully!', 'success');
                this.loadStudentListNew();
            } else {
                this.showMessage(data.message || 'Failed to delete student', 'error');
            }
        } catch (err) {
            this.showMessage('Network error. Please try again.', 'error');
        }
    }
}

// Initialize dashboard
const facultyDashboard = new FacultyDashboard();

// Global function for password toggle
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