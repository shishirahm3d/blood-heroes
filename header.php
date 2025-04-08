<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Heroes - Save Lives Through Blood Donation</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"> 
    <!-- https://fontawesome.com/start -->
    <!-- https://cdnjs.com/libraries/font-awesome -->
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="need_blood.php">Request Blood</a></li>
                <li><a href="blood_requests.php">View Requests</a></li>
                <li><a href="find_donor.php">Search Donors</a></li>
                <li><a href="register.php">Register as Donor</a></li>
                <li><a href="about.php">About Us</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <div class="logo">Blood Heroes</div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    <div class="dropdown">
                        <button class="dropbtn">My Account <i class="fas fa-caret-down"></i></button>
                        <div class="dropdown-content">
                            <a href="profile.php">My Profile</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login</a>
            <?php endif; ?>
        </div>
    </nav>

