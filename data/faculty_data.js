// Faculty Data Storage
// This file contains the faculty data structure and management functions

const FacultyDataManager = {
    // Get all faculty data from localStorage
    getAllFaculty: function() {
        try {
            const stored = localStorage.getItem('faculty_data');
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Error loading faculty data:', error);
            return [];
        }
    },

    // Save faculty data to localStorage
    saveFaculty: function(facultyList) {
        try {
            localStorage.setItem('faculty_data', JSON.stringify(facultyList));
            return true;
        } catch (error) {
            console.error('Error saving faculty data:', error);
            return false;
        }
    },

    // Add a new faculty member
    addFaculty: function(facultyData) {
        const facultyList = this.getAllFaculty();
        
        // Check if email already exists
        if (facultyList.some(f => f.email === facultyData.email)) {
            return { success: false, message: 'Faculty with this email already exists.' };
        }
        
        // Add new faculty
        facultyList.push(facultyData);
        
        // Save to localStorage
        if (this.saveFaculty(facultyList)) {
            return { success: true, message: 'Faculty added successfully.' };
        } else {
            return { success: false, message: 'Failed to save faculty data.' };
        }
    },

    // Find faculty by email
    findFacultyByEmail: function(email) {
        const facultyList = this.getAllFaculty();
        return facultyList.find(f => f.email === email);
    },

    // Authenticate faculty
    authenticateFaculty: function(email, password) {
        const faculty = this.findFacultyByEmail(email);
        if (faculty && faculty.password === password) {
            return { success: true, faculty: faculty };
        } else {
            return { success: false, message: 'Invalid email or password.' };
        }
    },

    // Get faculty count
    getFacultyCount: function() {
        return this.getAllFaculty().length;
    },

    // Clear all faculty data
    clearAllFaculty: function() {
        localStorage.removeItem('faculty_data');
    }
};

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FacultyDataManager;
} 