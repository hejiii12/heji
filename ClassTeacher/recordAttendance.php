<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $Lrn = $_POST['Lrn'];
    $status = $_POST['status'];

    $classId = $_SESSION['classId'];
    $classArmId = $_SESSION['classArmId'];

    // Set timezone to Asia/Manila
    date_default_timezone_set('Asia/Manila');

    // Get the current date and time in the format "Y-m-d h:i A"
    $dateTimeTaken = date("Y-m-d h:i A");

    $sql = "INSERT INTO tblattendance (Lrn, classId, classArmId, dateTimeTaken, status) 
            VALUES ('$Lrn', '$classId', '$classArmId', '$dateTimeTaken', '$status')
            ON DUPLICATE KEY UPDATE status='$status'";

    if (mysqli_query($conn, $sql)) {
        echo "Attendance recorded successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
