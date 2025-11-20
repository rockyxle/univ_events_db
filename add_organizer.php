<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');

if (isset($_POST['add_organizer'])) {

    $name = trim($_POST['o_name']);
    $contact_person = trim($_POST['o_contact_person']);
    $email = trim($_POST['o_email']);
    $contact_number = trim($_POST['o_contact']);
    $password = trim($_POST['o_password']);

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // insert into database table
    $insert_organizer_query = "
        INSERT INTO Organizers (OrganizerName, OrganizerContactPerson, EmailOfContactPerson, NumberOfContactPerson, OrganizerPassword)
        VALUES ('$name', '$contact_person', '$email', '$contact_number', '$hashed_password')
    ";

    // redirect back to organizers page
    if (mysqli_query($connection, $insert_organizer_query)) {
        header("location: organizers.php?insert_msg= New organizer added successfully");
        exit;
    } 
    else {
        
        die('Error in inserting new organizer: ' . mysqli_error($connection));
    }
} 
else {
    echo "Invalid submission";
}
?>
