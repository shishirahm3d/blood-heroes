<?php
include 'header.php';
include 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

// Get division and district names from the divisions and districts tables
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

// Calculate if user is available to donate (3 months since last donation)
$available = true;
$availability_message = "Available to donate";
if ($user['last_donation_date'] && $user['last_donation_date'] != 'Never donated') {
    $last_donation = new DateTime($user['last_donation_date']);
    $now = new DateTime();
    $interval = $now->diff($last_donation);
    $months = $interval->m + ($interval->y * 12);
    
    if ($months < 3) {
        $available = false;
        $availability_message = "Not available to donate (less than 3 months since last donation)";
    }
}

// Create images directory if it doesn't exist
if (!file_exists('images')) {
    mkdir('images', 0777, true);
    
    // Create default male profile image
    $male_image = imagecreatetruecolor(200, 200);
    $background = imagecolorallocate($male_image, 51, 153, 255); // Blue background
    $text_color = imagecolorallocate($male_image, 255, 255, 255); // White text
    imagefill($male_image, 0, 0, $background);
    imagestring($male_image, 5, 60, 90, "Male User", $text_color);
    imagepng($male_image, 'images/default_male.png');
    imagedestroy($male_image);
    
    // Create default female profile image
    $female_image = imagecreatetruecolor(200, 200);
    $background = imagecolorallocate($female_image, 255, 102, 204); // Pink background
    $text_color = imagecolorallocate($female_image, 255, 255, 255); // White text
    imagefill($female_image, 0, 0, $background);
    imagestring($female_image, 5, 50, 90, "Female User", $text_color);
    imagepng($female_image, 'images/default_female.png');
    imagedestroy($female_image);
}

// Default profile picture based on gender
$profile_pic = "images/default_male.png";
if ($user['gender'] == 'Female') {
    $profile_pic = "images/default_female.png";
}

// Create uploads directory if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// Create images directory if it doesn't exist
if (!file_exists('images')) {
    mkdir('images', 0777, true);
}


// If user has uploaded a profile picture, use that instead
if (!empty($user['profile_picture'])) {
    $profile_pic = $user['profile_picture'];
}
?>

<div class="container">
    <div class="profile-container">
        <h1>My Profile</h1>
        
        <div class="profile-header">
            <div class="profile-image">
                <img src="<?php echo $profile_pic; ?>" alt="Profile Picture">
            </div>
            <div class="profile-info">
                <h2><?php echo $user['full_name']; ?></h2>
                <p class="availability <?php echo $available ? 'available2' : 'unavailable2'; ?>">
                    <span class="status-dot"></span> <?php echo $availability_message; ?>
                </p>
                <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
        
        <div class="profile-details">
            <div class="detail-section">
                <h3>Personal Information</h3>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo $user['email']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Mobile Number:</div>
                    <div class="detail-value"><?php echo $user['mobile_number']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Gender:</div>
                    <div class="detail-value"><?php echo $user['gender']; ?></div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>Donor Information</h3>
                <div class="detail-row">
                    <div class="detail-label">Blood Group:</div>
                    <div class="detail-value"><?php echo $user['blood_group']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Age:</div>
                    <div class="detail-value"><?php echo $user['age']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Weight:</div>
                    <div class="detail-value"><?php echo $user['weight']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Last Donation Date:</div>
                    <div class="detail-value"><?php echo $user['last_donation_date']; ?></div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>Location Information</h3>
                <div class="detail-row">
                    <div class="detail-label">Division:</div>
                    <div class="detail-value"><?php echo $user['division']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">District:</div>
                    <div class="detail-value"><?php echo $user['district']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Area:</div>
                    <div class="detail-value"><?php echo $user['area']; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

