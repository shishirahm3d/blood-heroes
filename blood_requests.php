<?php
include 'header.php';
include 'db_connect.php';

// Get filter values
$blood_group = isset($_GET['blood_group']) ? $_GET['blood_group'] : '';
$division_id = isset($_GET['division_id']) ? $_GET['division_id'] : '';
$district_id = isset($_GET['district_id']) ? $_GET['district_id'] : '';

// Build query - only show requests that are less than 5 days old
$sql = "SELECT br.*, d1.division_name, d2.district_name 
        FROM blood_requests br
        JOIN divisions d1 ON br.division_id = d1.division_id
        JOIN districts d2 ON br.district_id = d2.district_id
        WHERE DATEDIFF(CURRENT_DATE, DATE(br.created_at)) < 5";

$params = [];
$types = "";

if ($blood_group) {
    $sql .= " AND br.blood_group = ?";
    $params[] = $blood_group;
    $types .= "s";
}

if ($division_id) {
    $sql .= " AND br.division_id = ?";
    $params[] = $division_id;
    $types .= "i";
}

if ($district_id) {
    $sql .= " AND br.district_id = ?";
    $params[] = $district_id;
    $types .= "i";
}

$sql .= " ORDER BY br.created_at DESC";

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
        <h2>Blood Donation Requests</h2>
        
        <div class="filter-section">
            <h3>Filter Requests</h3>
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
                <a href="blood_requests.php" class="btn btn-outline">Clear Filters</a>
            </form>
        </div>
        
        <div class="requests-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while($request = $result->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div class="blood-group"><?php echo $request['blood_group']; ?></div>
                            <div class="request-date">
                                <?php echo date('M d, Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="request-body">
                            <h3><?php echo $request['patient_name']; ?></h3>
                            
                            <div class="request-details">
                                <div class="detail-item">
                                    <span class="detail-label">Location:</span>
                                    <span class="detail-value"><?php echo $request['division_name'] . ', ' . $request['district_name']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Hospital:</span>
                                    <span class="detail-value"><?php echo $request['hospital']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Bags Needed:</span>
                                    <span class="detail-value"><?php echo $request['bags_needed']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Needed By:</span>
                                    <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($request['needed_time'])); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Reason:</span>
                                    <span class="detail-value"><?php echo $request['reason']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="request-footer">
                            <a href="tel:<?php echo $request['contact_number']; ?>" class="btn btn-primary">
                                <i class="fas fa-phone"></i> Call <?php echo $request['contact_number']; ?>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No blood requests found matching your criteria.</p>
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