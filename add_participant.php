<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__ . '/connect_to_db.php');

if (isset($_POST['add_participant'])) {
    $lastname   = trim($_POST['p_lastname']);
    $firstname  = trim($_POST['p_firstname']);
    $initial    = trim($_POST['p_initial']);
    $email      = trim($_POST['p_email']);
    $contact    = trim($_POST['p_contact']);
    $year_level = isset($_POST['p_yearlevel']) ? intval($_POST['p_yearlevel']) : 1;
    
    $course_id  = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;

    $stmt = $connection->prepare("
        INSERT INTO Participants 
        (ParticipantLastName, ParticipantFirstName, ParticipantInitial, ParticipantEmail, ParticipantContactNumber, ParticipantYearLevel, CourseID) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }


    $stmt->bind_param("sssssii", 
        $lastname, 
        $firstname, 
        $initial, 
        $email, 
        $contact, 
        $year_level, 
        $course_id
    );

    if ($stmt->execute()) {
        $stmt->close();
        $connection->close();
        header("Location: participants.php?insert_msg=New participant added successfully");
        exit;
    } else {
        $stmt->close();
        $connection->close();
        die("Error adding participant: " . $connection->error);
    }
} else {
    header("Location: participants.php");
    exit;
}
?>