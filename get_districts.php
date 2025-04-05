<?php
include 'db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['division_id'])) {
    $division_id = $_GET['division_id'];
    
    $sql = "SELECT * FROM districts WHERE division_id = ? ORDER BY district_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $division_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $districts = [];
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    
    echo json_encode($districts);
} else {
    echo json_encode([]);
}
?>

