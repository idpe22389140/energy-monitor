<?php
// ============================================================
// config/db.php - Σύνδεση με Βάση Δεδομένων
// Energy Monitoring System
// ============================================================

$host   = "localhost";
$user   = "root";
$pass   = "";          // XAMPP default: κενό password
$dbname = "energy_monitor";

$con = mysqli_connect($host, $user, $pass, $dbname);

if (!$con) {
    die("<h2 style='color:red;font-family:sans-serif;padding:20px;'>
         ❌ Αποτυχία σύνδεσης με τη βάση δεδομένων!<br>
         <small>" . mysqli_connect_error() . "</small>
         </h2>");
}

mysqli_set_charset($con, "utf8mb4");
?>
