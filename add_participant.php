<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__ . '/connect_to_db.php');

if (isset($_POST['add_participant'])) {
    $lastname = trim($_POST['p_lastname']);
    $firstname = trim($_POST['p_firstname']);
    $initial = trim($_POST['p_initial']);
    $email = trim($_POST['p_email']);
    $contact = trim($_POST['p_contact']);
    $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : 'NULL';

    $insert_query = "
        INSERT INTO Participants
        (ParticipantLastName, ParticipantFirstName, ParticipantInitial, ParticipantEmail, ParticipantContactNumber, CourseID)
        VALUES
        ('$lastname', '$firstname', '$initial', '$email', '$contact', $course_id)
    ";

    if (mysqli_query($connection, $insert_query)) {
        header("Location: participants.php?insert_msg=New participant added successfully");
        exit;
    } else {
        die("Error adding participant: " . mysqli_error($connection));
    }
}
?>
