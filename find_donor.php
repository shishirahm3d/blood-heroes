<?php
include 'header.php';
include 'db_connect.php';

// Get filter values
$blood_group = isset($_GET['blood_group']) ? $_GET['blood_group'] : '';
$division_id = isset($_GET['division_id']) ? $_GET['division_id'] : '';
$district_id = isset($_GET['district_id']) ? $_GET['district_id'] : '';

// Build query
$sql = "SELECT d.donor_id, d.user_id, d.blood_group, d.age, d.weight, d.last_donation_date, d.division_id, d.district_id, d.area, d.is_available, 
               u.full_name, u.mobile_number, division_table.division_name, district_table.district_name 
        FROM donors d
        JOIN users u ON d.user_id = u.user_id
        JOIN divisions division_table ON d.division_id = division_table.division_id
        JOIN districts district_table ON d.district_id = district_table.district_id
        WHERE 1=1";

$params = [];
$types = "";

if ($blood_group) {
    $sql .= " AND d.blood_group = ?";
    $params[] = $blood_group;
    $types .= "s";
}

if ($division_id) {
    $sql .= " AND d.division_id = ?";
    $params[] = $division_id;
    $types .= "i";
}

if ($district_id) {
    $sql .= " AND d.district_id = ?";
    $params[] = $district_id;
    $types .= "i";
}

// Order by availability (available donors will show first)
$sql .= " ORDER BY d.is_available DESC, u.full_name ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get divisions for filter
$divisions_sql = "SELECT * FROM divisions ORDER BY division_name";
$divisions_result = $conn->query($divisions_sql);
?>

<section class="list-section">
    <div class="list-container">
        <h2>Find Blood Donors</h2>
        
        <div class="filter-section">
            <h3>Filter Donors</h3>
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group">
                            <option value="">All Blood Groups</option>
                            <option value="A+" <?php if($blood_group == 'A+') echo 'selected'; ?>>A+</option>
                            <option value="A-" <?php if($blood_group == 'A-') echo 'selected'; ?>>A-</option>
                            <option value="B+" <?php if($blood_group == 'B+') echo 'selected'; ?>>B+</option>
                            <option value="B-" <?php if($blood_group == 'B-') echo 'selected'; ?>>B-</option>
                            <option value="AB+" <?php if($blood_group == 'AB+') echo 'selected'; ?>>AB+</option>
                            <option value="AB-" <?php if($blood_group == 'AB-') echo 'selected'; ?>>AB-</option>
                            <option value="O+" <?php if($blood_group == 'O+') echo 'selected'; ?>>O+</option>
                            <option value="O-" <?php if($blood_group == 'O-') echo 'selected'; ?>>O-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="division_id">Division</label>
                        <select id="division_id" name="division_id" onchange="loadDistricts()">
                            <option value="">All Divisions</option>
                            <?php 
                            $divisions_result->data_seek(0);
                            while($division = $divisions_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $division['division_id']; ?>" <?php if($division_id == $division['division_id']) echo 'selected'; ?>>
                                    <?php echo $division['division_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="district_id">District</label>
                        <select id="district_id" name="district_id">
                            <option value="">All Districts</option>
                            <?php 
                            if ($division_id) {
                                $districts_sql = "SELECT * FROM districts WHERE division_id = ? ORDER BY district_name";
                                $districts_stmt = $conn->prepare($districts_sql);
                                $districts_stmt->bind_param("i", $division_id);
                                $districts_stmt->execute();
                                $districts_result = $districts_stmt->get_result();
                                
                                while($district = $districts_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $district['district_id']; ?>" <?php if($district_id == $district['district_id']) echo 'selected'; ?>>
                                        <?php echo $district['district_name']; ?>
                                    </option>
                                <?php endwhile;
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="find_donor.php" class="btn btn-outline">Clear Filters</a>
            </form>
        </div>
        
        <div class="donors-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while($donor = $result->fetch_assoc()): ?>
                    <div class="donor-card <?php echo $donor['is_available'] ? 'available' : 'unavailable'; ?>">
                        <div class="donor-header">
                            <div class="blood-group"><?php echo $donor['blood_group']; ?></div>
                            <div class="availability-indicator">
                                <span class="status-dot"></span>
                                <span class="status-text"><?php echo $donor['is_available'] ? 'Available' : 'Unavailable'; ?></span>
                            </div>
                        </div>
                        
                        <div class="donor-body">
                            <h3><?php echo $donor['full_name']; ?></h3>
                            
                            <div class="donor-details">
                                <div class="detail-item">
                                    <span class="detail-label">Location:</span>
                                    <span class="detail-value"><?php echo $donor['division_name'] . ', ' . $donor['district_name']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Area:</span>
                                    <span class="detail-value"><?php echo $donor['area']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Age:</span>
                                    <span class="detail-value"><?php echo $donor['age']; ?> years</span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Weight:</span>
                                    <span class="detail-value"><?php echo $donor['weight']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Last Donation:</span>
                                    <span class="detail-value">
                                        <?php 
                                        if ($donor['last_donation_date']) {
                                            echo date('M d, Y', strtotime($donor['last_donation_date']));
                                        } else {
                                            echo 'Never donated';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="donor-footer">
                            <a href="tel:<?php echo $donor['mobile_number']; ?>" class="btn btn-primary">
                                <i class="fas fa-phone"></i> Call <?php echo $donor['mobile_number']; ?>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No donors found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
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
                districtSelect.innerHTML = '<option value="">All Districts</option>';
                
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
        districtSelect.innerHTML = '<option value="">All Districts</option>';
    }
}
</script>

<?php include 'footer.php'; ?>
