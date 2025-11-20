<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    include('connect_to_db.php');

    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        // placeholder '?'
        $query = "DELETE FROM Events WHERE EventID = ?";
        $stmt = mysqli_prepare($connection, $query);
        // put data into placeholder
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: events.php?delete_msg=Event deleted successfully");
            exit;
        } 
        else {
            die("Deletion failed: " . mysqli_error($connection));
        }
    } 
    else {
        header("Location: events.php?error=No event ID found");
        exit;
    }
?>
