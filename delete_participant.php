<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__ . '/connect_to_db.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete_query = "DELETE FROM Participants WHERE ParticipantID = $id";

    if (mysqli_query($connection, $delete_query)) {
        header("Location: participants.php?delete_msg=Participant deleted successfully");
        exit;
    } else {
        die("Error deleting participant: " . mysqli_error($connection));
    }
} else {
    die('Participant ID missing.');
}
?>
