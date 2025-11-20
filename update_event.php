<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');

// get event ID based on current URL
if(!isset($_GET['id'])) {
    die('No event found');
}

$event_id = intval($_GET['id']);

// fetch the event data based on the ID
$event_query = "
    SELECT * FROM Events WHERE EventID = $event_id
    ";

    $event_result = mysqli_query($connection, $event_query);
    if (!$event_result || mysqli_num_rows($event_result) === 0) {
        die('Event not found');
    }
$event = mysqli_fetch_assoc($event_result);

// fetch venues list
$venue_query = "SELECT EventVenueID, EventVenueName FROM EventVenues ORDER BY EventVenueName ASC";
$venue_result = mysqli_query($connection, $venue_query);

// handling the forms
if(isset($_POST['update_event'])) {
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

    //query for updating event (inserting updated data to DB)
    $update_query = "
        UPDATE Events
        SET EventName='$name', EventDate='$date', EventCost=$cost, EventVenueID=$venue_id
        WHERE EventID=$event_id
    ";

    if (mysqli_query($connection, $update_query)) {
        header('location: events.php?update_msg=Event updated successfully');
        exit;
    }
    else {
        die('Error in updating event: '. mysqli_error($connection));
    }
}
    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Event</title>
    <link href="https://fonts.cdnfonts.com/css/garet" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/arimo" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="mx-auto ">
        <div class="d-flex justify-content-center mt-3">
            <h1 class="header-update-text">Event Update</h1>
        </div>
    <div class="container mt-5">
        <form method="POST">
            <div class="form-group mb-3">
                <label for="e_name">Event Name</label>
                <input type="text" id="e_name" name="e_name" class="form-control" maxlength="150"required value="<?php echo htmlspecialchars($event['EventName']); ?>">
                <small id="eventNameLimitMsg" class="text-danger" style="display:none;">
                        You have reached the maximum length for the Event Name
                </small>
            </div>

            <div class="form-group mb-3">
                <label for="e_date">Event Date</label>
                <input type="date" name="e_date" class="form-control" required value="<?php echo $event['EventDate']; ?>">
            </div>

            <div class="form-group mb-3">
                <label for="e_cost">Event Cost (Php)</label>
                <input type="text" id="e_cost" inputmode="decimal" name="e_cost" class="form-control" pattern="^\d{1,6}(\.\d{1,2})?$" title="Enter a valid amount (0 to 999999.99)"  
                    maxlength="9"  required value="<?php echo $event['EventCost']; ?>">
            </div>

            <div class="form-group mb-3">
                <label for="e_venue">Event Venue</label>
                <select name="e_venue" id="e_venue" class="form-select" required onchange="toggleNewVenueField()">
                    <option value="">Select the Venue for the Event</option>
                    <?php while ($venue = mysqli_fetch_assoc($venue_result)): ?>
                        <option value="<?php echo $venue['EventVenueID']; ?>" <?php if ($venue['EventVenueID'] == $event['EventVenueID']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($venue['EventVenueName']); ?>
                        </option>
                    <?php endwhile; ?>
                    <option value="new">+ Add New Venue</option>
                </select>
            </div>

            <div class="form-group mb-3" id="newVenueField" style="display: none;">
                <label for="new_event_venue">New Venue Name</label>
                <input type="text" id ="new_event_venue" name="new_event_venue" class="form-control"
                 maxlength="150" placeholder="Enter name of new venue">


                 <small id="eventVenueNameLimitMsg" class="text-danger" style="display:none;">
                    You have reached the maximum length for the Event Venue Name
                </small>
            </div>

            <button type="submit" name="update_event" class="btn btn-primary">Update Event</button>
            <a href="events.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    
<script>
    function toggleNewVenueField() {
        const venueSelect = document.getElementById('e_venue');
        const newVenueField = document.getElementById('newVenueField');
        newVenueField.style.display = (venueSelect.value === 'new') ? 'block' : 'none';
    }
</script>
<script src="script.js"></script>
</body>
</html>