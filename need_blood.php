<?php
include 'header.php';
include 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $patient_name = $_POST['patient_name'];
    $division_id = $_POST['division_id'];
    $district_id = $_POST['district_id'];
    $hospital = $_POST['hospital'];
    $blood_group = $_POST['blood_group'];
    $reason = $_POST['reason'];
    $contact_number = $_POST['contact_number'];
    $bags_needed = $_POST['bags_needed'];
    $needed_time = $_POST['needed_date'] . ' ' . $_POST['needed_time'];
    
    // Insert into blood_requests table
    $sql = "INSERT INTO blood_requests (patient_name, division_id, district_id, hospital, blood_group, reason, contact_number, bags_needed, needed_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siissssss", $patient_name, $division_id, $district_id, $hospital, $blood_group, $reason, $contact_number, $bags_needed, $needed_time);
    
    if ($stmt->execute()) {
        $success = "Blood request submitted successfully!";
    } else {
        $error = "Error submitting request: " . $conn->error;
    }
}

// Get divisions for dropdown
$divisions_sql = "SELECT * FROM divisions ORDER BY division_name";
$divisions_result = $conn->query($divisions_sql);
?>

<section class="form-section">
    <div class="form-container">
        <h2>Request Blood Donation</h2>
        
        <?php if($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="patient_name">Patient Name</label>
                <input type="text" id="patient_name" name="patient_name" required>
            </div>
            
            <div class="form-row">
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
            </div>
            
            <div class="form-group">
                <label for="hospital">Hospital</label>
                <input type="text" id="hospital" name="hospital" required>
            </div>
            
            <div class="form-row">
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
                    <label for="bags_needed">Bags Needed</label>
                    <input type="number" id="bags_needed" name="bags_needed" min="1" max="10" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason">Reason for Blood Need</label>
                <textarea id="reason" name="reason" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="needed_date">Date Needed</label>
                    <input type="date" id="needed_date" name="needed_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="needed_time">Time Needed</label>
                    <input type="time" id="needed_time" name="needed_time" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
        </form>
    </div>
</section>

<script>
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
