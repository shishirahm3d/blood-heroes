 
// Global JavaScript functions

// Function to toggle password visibility
function togglePasswordVisibility(passwordId, iconId) {
    const passwordInput = document.getElementById(passwordId);
    const icon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Function to handle the "never donated" checkbox
function toggleLastDonationDate() {
    const lastDonationDate = document.getElementById('last_donation_date');
    const neverDonated = document.getElementById('never_donated');
    
    if (neverDonated.checked) {
        lastDonationDate.value = '';
        lastDonationDate.disabled = true;
    } else {
        lastDonationDate.disabled = false;
    }
}

// Function to load districts based on selected division
function loadDistricts() {
    const divisionId = document.getElementById('division_id').value;
    const districtSelect = document.getElementById('district_id');
    
    if (!districtSelect) return;
    
    // Clear current options
    districtSelect.innerHTML = '<option value="">Loading districts...</option>';
    
    if (divisionId) {
        // Fetch districts via AJAX
        fetch(`get_districts.php?division_id=${divisionId}`)
            .then(response => response.json())
            .then(districts => {
                // Check if we're on the filter page or form page
                const isFilterPage = window.location.href.includes('find_donor.php') || 
                                    window.location.href.includes('blood_requests.php');
                
                districtSelect.innerHTML = isFilterPage ? 
                    '<option value="">All Districts</option>' : 
                    '<option value="">Select District</option>';
                
                districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.district_id;
                    option.textContent = district.district_name;
                    districtSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading districts:', error);
                districtSelect.innerHTML = '<option value="">Error loading districts</option>';
            });
    } else {
        const isFilterPage = window.location.href.includes('find_donor.php') || 
                            window.location.href.includes('blood_requests.php');
        
        districtSelect.innerHTML = isFilterPage ? 
            '<option value="">All Districts</option>' : 
            '<option value="">Select Division First</option>';
    }
}

// Initialize any elements that need JavaScript when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize district dropdown if division is already selected
    const divisionSelect = document.getElementById('division_id');
    if (divisionSelect && divisionSelect.value) {
        loadDistricts();
    }
    
    // Initialize "never donated" checkbox
    const neverDonated = document.getElementById('never_donated');
    if (neverDonated) {
        neverDonated.addEventListener('change', toggleLastDonationDate);
    }
});