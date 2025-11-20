<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('connect_to_db.php');

if (isset($_POST['add_event'])) {
    $name = trim($_POST['e_name']);
    $date = $_POST['e_date'];
    $cost = floatval($_POST['e_cost']);
    $venue_id = $_POST['e_venue'];
    $new_venue = trim($_POST['new_event_venue']);



    // option if user wants to add a new venue
    if ($venue_id === 'new' && !empty($new_venue)) {
        $insert_venue_query = "INSERT INTO EventVenues (EventVenueName) VALUES ('$new_venue')";
        if (!mysqli_query($connection, $insert_venue_query)) {
            die('Error inserting new venue: ' . mysqli_error($connection));
        }
        $venue_id = mysqli_insert_id($connection);
    }

    // inserting new event to database
    $insert_event_query = "
        INSERT INTO Events (EventName, EventDate, EventCost, EventVenueID)
        VALUES ('$name', '$date', $cost, $venue_id)
    ";

    // redirect back to events page
    if (mysqli_query($connection, $insert_event_query)) {
        header("location: events.php?insert_msg=New event added successfully"); 
        exit;
    } 

    else {
        die('Error in inserting event: ' . mysqli_error($connection));
    }
} 
else {
    echo "Invalid submission";
}
?>
