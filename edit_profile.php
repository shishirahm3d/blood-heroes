<?php
include 'header.php';
include 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get user information
$sql = "SELECT u.*, d.blood_group, d.age, d.weight, d.last_donation_date, d.division_id, d.district_id, d.area 
        FROM users u 
        LEFT JOIN donors d ON u.user_id = d.user_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get division and district names
$division_sql = "SELECT division_name FROM divisions WHERE division_id = ?";
$district_sql = "SELECT district_name FROM districts WHERE district_id = ?";

// Get division name
if (!empty($user['division_id'])) {
    $division_stmt = $conn->prepare($division_sql);
    $division_stmt->bind_param("i", $user['division_id']);
    $division_stmt->execute();
    $division_result = $division_stmt->get_result();
    $division = $division_result->fetch_assoc();
    $user['division'] = $division['division_name'] ?? 'Not specified';
} else {
    $user['division'] = 'Not specified';
}

// Get district name
if (!empty($user['district_id'])) {
    $district_stmt = $conn->prepare($district_sql);
    $district_stmt->bind_param("i", $user['district_id']);
    $district_stmt->execute();
    $district_result = $district_stmt->get_result();
    $district = $district_result->fetch_assoc();
    $user['district'] = $district['district_name'] ?? 'Not specified';
} else {
    $user['district'] = 'Not specified';
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $mobile_number = $_POST['mobile_number'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $age = $_POST['age'];
    $weight = $_POST['weight'];
    //$last_donation_date = $_POST['last_donation_date'];
    $last_donation_date = isset($_POST['last_donation_date']) && !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : NULL;
    $division_id = $_POST['division_id'];
    $district_id = $_POST['district_id'];
    $area = $_POST['area'];
    
    // Check if email already exists for another user
    $check_email = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Email already exists. Please use a different email.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update users table
            $update_user = "UPDATE users SET full_name = ?, email = ?, mobile_number = ?, gender = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_user);
            $stmt->bind_param("ssssi", $full_name, $email, $mobile_number, $gender, $user_id);
            $stmt->execute();
            
            // Update donors table with correct column names
            $update_donor = "UPDATE donors SET blood_group = ?, age = ?, weight = ?, last_donation_date = ?, division_id = ?, district_id = ?, area = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_donor);
            $stmt->bind_param("sssssssi", $blood_group, $age, $weight, $last_donation_date, $division_id, $district_id, $area, $user_id);
            $stmt->execute();
            
            // Handle password change if provided
            if (!empty($_POST['new_password'])) {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $update_password = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_password);
                $stmt->bind_param("si", $new_password, $user_id);
                $stmt->execute();
            }
            
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                // Create uploads directory if it doesn't exist
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_picture']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
                    $upload_path = 'uploads/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        $update_picture = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($update_picture);
                        $stmt->bind_param("si", $upload_path, $user_id);
                        $stmt->execute();
                    } else {
                        throw new Exception("Failed to upload profile picture.");
                    }
                } else {
                    throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Update session data
            $_SESSION['full_name'] = $full_name;
            
            $message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            // Refresh division and district names
            if (!empty($user['division_id'])) {
                $division_stmt = $conn->prepare($division_sql);
                $division_stmt->bind_param("i", $user['division_id']);
                $division_stmt->execute();
                $division_result = $division_stmt->get_result();
                $division = $division_result->fetch_assoc();
                $user['division'] = $division['division_name'] ?? 'Not specified';
            }
            
            if (!empty($user['district_id'])) {
                $district_stmt = $conn->prepare($district_sql);
                $district_stmt->bind_param("i", $user['district_id']);
                $district_stmt->execute();
                $district_result = $district_stmt->get_result();
                $district = $district_result->fetch_assoc();
                $user['district'] = $district['district_name'] ?? 'Not specified';
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Get all divisions for dropdown
$divisions_query = "SELECT division_id, division_name FROM divisions ORDER BY division_name";
$divisions_result = $conn->query($divisions_query);

// Default profile picture based on gender
$profile_pic = "images/default_male.png";
if ($user['gender'] == 'Female') {
    $profile_pic = "images/default_female.png";
}

// If user has uploaded a profile picture, use that instead
if (!empty($user['profile_picture'])) {
    $profile_pic = $user['profile_picture'];
}
?>

<div class="container">
    <div class="edit-profile-container">
        <h1>Edit Profile</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="edit_profile.php" method="post" enctype="multipart/form-data">
            <div class="profile-image-upload">
                <div class="current-image">
                    <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" id="profile-preview">
                </div>
                <div class="upload-controls">
                    <label for="profile_picture" class="btn btn-outline">Change Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display: none;">
                    <p class="help-text">Allowed formats: JPG, JPEG, PNG, GIF</p>
                </div>
            </div>
            
            <div class="form-sections">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number</label>
                        <input type="text" id="mobile_number" name="mobile_number" value="<?php echo $user['mobile_number']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($user['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Donor Information</h3>
                    
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="A+" <?php echo ($user['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo ($user['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo ($user['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo ($user['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo ($user['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo ($user['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+" <?php echo ($user['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo ($user['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" min="18" max="100" value="<?php echo $user['age']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">Weight</label>
                        <select id="weight" name="weight" required>
                            <option value="45-50" <?php echo ($user['weight'] == '45-50') ? 'selected' : ''; ?>>45-50 kg</option>
                            <option value="51-60" <?php echo ($user['weight'] == '51-60') ? 'selected' : ''; ?>>51-60 kg</option>
                            <option value="61-70" <?php echo ($user['weight'] == '61-70') ? 'selected' : ''; ?>>61-70 kg</option>
                            <option value="71-80" <?php echo ($user['weight'] == '71-80') ? 'selected' : ''; ?>>71-80 kg</option>
                            <option value="81+" <?php echo ($user['weight'] == '81+') ? 'selected' : ''; ?>>81+ kg</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_donation_date">Last Donation Date</label>
                        <input type="date" id="last_donation_date" name="last_donation_date" 
                               value="<?php echo ($user['last_donation_date'] != 'Never donated') ? $user['last_donation_date'] : ''; ?>">
                        <div class="checkbox-group">
                            <input type="checkbox" id="never_donated" name="never_donated" 
                                   <?php echo ($user['last_donation_date'] == 'Never donated') ? 'checked' : ''; ?>>
                            <label for="never_donated">I have never donated blood</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Location Information</h3>
                    
                    <div class="form-group">
                        <label for="division_id">Division</label>
                        <select id="division_id" name="division_id" required>
                            <?php while ($division = $divisions_result->fetch_assoc()): ?>
                                <option value="<?php echo $division['division_id']; ?>" <?php echo ($user['division_id'] == $division['division_id']) ? 'selected' : ''; ?>>
                                    <?php echo $division['division_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="district_id">District</label>
                        <select id="district_id" name="district_id" required>
                            <option value="<?php echo $user['district_id']; ?>"><?php echo $user['district']; ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="area">Area</label>
                        <input type="text" id="area" name="area" value="<?php echo $user['area']; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="profile.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Preview profile picture before upload
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Handle "never donated" checkbox
document.getElementById('never_donated').addEventListener('change', function() {
    const dateInput = document.getElementById('last_donation_date');
    if (this.checked) {
        dateInput.value = '';
        dateInput.disabled = true;
    } else {
        dateInput.disabled = false;
    }
});

// Initialize the "never donated" checkbox state
window.addEventListener('DOMContentLoaded', function() {
    const neverDonated = document.getElementById('never_donated');
    const dateInput = document.getElementById('last_donation_date');
    if (neverDonated.checked) {
        dateInput.disabled = true;
    }
});

// Load districts based on division selection
document.getElementById('division_id').addEventListener('change', function() {
    const divisionId = this.value;
    const districtSelect = document.getElementById('district_id');
    
    // Clear current options
    districtSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Fetch districts for selected division
    fetch('get_districts.php?division_id=' + encodeURIComponent(divisionId))
        .then(response => response.json())
        .then(data => {
            districtSelect.innerHTML = '';
            data.forEach(district => {
                const option = document.createElement('option');
                option.value = district.district_id;
                option.textContent = district.district_name;
                districtSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching districts:', error);
            districtSelect.innerHTML = '<option value="">Error loading districts</option>';
        });
});

// Load districts on page load
window.addEventListener('DOMContentLoaded', function() {
    const divisionId = document.getElementById('division_id').value;
    const districtSelect = document.getElementById('district_id');
    const currentDistrictId = '<?php echo $user['district_id']; ?>';
    
    if (divisionId) {
        fetch('get_districts.php?division_id=' + encodeURIComponent(divisionId))
            .then(response => response.json())
            .then(data => {
                districtSelect.innerHTML = '';
                data.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.district_id;
                    option.textContent = district.district_name;
                    if (district.district_id == currentDistrictId) {
                        option.selected = true;
                    }
                    districtSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching districts:', error);
            });
    }
});
</script>

<?php include 'footer.php'; ?>

