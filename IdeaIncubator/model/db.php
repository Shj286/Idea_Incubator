<?php
// db.php: Database connection info and helper function

function db_connect() {

    $host = 'localhost';
    $user = 'w3sjangra';
    $pass = 'w3sjangra136';
    $db   = 'C354_w3sjangra';

    $conn = mysqli_connect($host, $user, $pass, $db);
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        return false;
    }
    
    return $conn;
}
?>