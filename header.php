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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="need_blood.php">I Need Blood</a></li>
                <li><a href="blood_requests.php">Those Who Need Blood</a></li>
                <li><a href="find_donor.php">Find a Donor</a></li>
                <li><a href="register.php">I Want To Donate Blood</a></li>
                <li><a href="about.php">About Us</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <div class="logo">Blood Heroes</div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login</a>
            <?php endif; ?>
        </div>
    </nav>