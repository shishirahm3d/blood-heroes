<?php
include 'header.php';
include 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $mobile_number = $_POST['mobile_number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender']; // Added gender field
    $blood_group = $_POST['blood_group'];
    $age = $_POST['age'];
    $weight = $_POST['weight'];
    $last_donation_date = $_POST['last_donation_date'] ? $_POST['last_donation_date'] : NULL;
    $division_id = $_POST['division_id'];
    $district_id = $_POST['district_id'];
    $area = $_POST['area'];
    
    // Check if email or mobile already exists
    $check_sql = "SELECT * FROM users WHERE email = ? OR mobile_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $email, $mobile_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Email or mobile number already registered";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert into users table with gender
            $user_sql = "INSERT INTO users (full_name, email, mobile_number, password, gender) VALUES (?, ?, ?, ?, ?)";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->bind_param("sssss", $full_name, $email, $mobile_number, $password, $gender);
            $user_stmt->execute();
            
            // Get the user_id
            $user_id = $conn->insert_id;
            
            // Insert into donors table
            $donor_sql = "INSERT INTO donors (user_id, blood_group, age, weight, last_donation_date, division_id, district_id, area) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $donor_stmt = $conn->prepare($donor_sql);
            $donor_stmt->bind_param("isissiis", $user_id, $blood_group, $age, $weight, $last_donation_date, $division_id, $district_id, $area);
            $donor_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $success = "Registration successful! You can now login.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

// Get divisions for dropdown
$divisions_sql = "SELECT * FROM divisions ORDER BY division_name";
$divisions_result = $conn->query($divisions_sql);
?>

<section class="auth-section">
    <div class="auth-container registration">
        <h2>Register as a Blood Donor</h2>
        
        <?php if($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-sections">
                <div class="form-section">
                    <h3>Login Information</h3>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number</label>
                        <input type="tel" id="mobile_number" name="mobile_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Donor Information</h3>
                    
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" min="18" max="65" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">Weight</label>
                        <select id="weight" name="weight" required>
                            <option value="">Select Weight (Minimum 45kg)</option>
                            <option value="45-50 kg">45-50 kg</option>
                            <option value="50-60 kg">50-60 kg</option>
                            <option value="60-70 kg">60-70 kg</option>
                            <option value="70-80 kg">70-80 kg</option>
                            <option value="80-90 kg">80-90 kg</option>
                            <option value="90+ kg">90+ kg</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_donation_date">Last Donation Date</label>
                        <input type="date" id="last_donation_date" name="last_donation_date">
                        <div class="checkbox-group">
                            <input type="checkbox" id="never_donated" onchange="toggleLastDonationDate()">
                            <label for="never_donated">I have never donated blood</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="division_id">Division</label>
                        <select id="division_id" name="division_id" required onchange="loadDistricts()">
                            <option value="">Select Division</option>
                            <?php while($division = $divisions_result->fetch_assoc()): ?>
                                <option value="<?php echo $division['division_id']; ?>">
                                    <?php echo $division['division_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="district_id">District</label>
                        <select id="district_id" name="district_id" required>
                            <option value="">Select Division First</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="area">Area</label>
                        <textarea id="area" name="area" required></textarea>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        
        <div class="auth-links">
            <p>Already registered? <a href="login.php">Login here</a></p>
        </div>
    </div>
</section>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const icon = document.querySelector('.toggle-password i');
    
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

function loadDistricts() {
    const divisionId = document.getElementById('division_id').value;
    const districtSelect = document.getElementById('district_id');
    
    // Clear current options
    districtSelect.innerHTML = '<option value="">Loading districts...</option>';
    
    if (divisionId) {
        // Fetch districts via AJAX
        fetch(`get_districts.php?division_id=${divisionId}`)
            .then(response => response.json())
            .then(districts => {
                districtSelect.innerHTML = '<option value="">Select District</option>';
                
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
        districtSelect.innerHTML = '<option value="">Select Division First</option>';
    }
}
</script>

<?php include 'footer.php'; ?>

