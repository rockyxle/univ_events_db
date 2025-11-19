<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');
include('check_user_role.php');

$query = "
SELECT 
    e.EventID,
    e.EventName,
    v.EventVenueName,
    e.EventDate,
    e.EventCost,
    COUNT(p.ParticipantID) AS NumberOfParticipants
FROM 
    Events e
JOIN 
    EventVenues v ON e.EventVenueID = v.EventVenueID

LEFT JOIN 
    EventParticipants p ON e.EventID = p.EventID

LEFT JOIN
    EventOrganizers eo ON e.EventID = eo.EventID

GROUP BY 
    e.EventID, e.EventName, v.EventVenueName, e.EventDate, e.EventCost

ORDER BY e.EventDate ASC
";

$result = mysqli_query($connection, $query);
if(!$result) {
    die('Query failed: ' . mysqli_error($connection));
}

// for venue dropdown
$venue_query = "SELECT EventVenueID, EventVenueName FROM EventVenues ORDER BY EventVenueName ASC";
$venue_result = mysqli_query($connection, $venue_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events</title>
  <link href="https://fonts.cdnfonts.com/css/garet" rel="stylesheet">
  <link href="https://fonts.cdnfonts.com/css/arimo" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>

<body>

<!--navbar + search bar-->    
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top bg-primary">
    <div class="container py-2 justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <a href="<?php echo htmlspecialchars($backButton); ?>" class="btn btn-outline-light btn-sm me-3"">
          Back
       </a>
        <h2 class="mb-0 fw-bold text-white">Events Dashboard</h2>
      </div>

        <!-- search bar -->
        <div class="search-bar d-flex">
            <input type="text" placeholder="Search events...">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </div>
    </div>
</nav>


<div class="container my-4">
    <!-- header +add event button -->
    <div class="events-header mx-auto">
        <div class="d-flex justify-content-center position-relative px-4 py-3">
            <h1 class="header-text mb-0 text-center">events</h1>
            <button class="btn btn-light add-event-btn position-absolute end-0 me-4" data-bs-toggle="modal" data-bs-target="#exampleModal">
                + Add Event
            </button>
        </div>
    </div>

    <!-- main content (list of events) -->
  <div class="list-item-container p-4 rounded shadow-sm mt-3">

    <?php while($row = mysqli_fetch_assoc($result)): ?>
    <div class="event-row" role="article" aria-labelledby="ev-<?php echo $row['EventID']; ?>">

        <!-- event name and date -->
        <div class="d-flex justify-content-between align-items-center">
        <div>
            <div id="ev-<?php echo $row['EventID']; ?>" class="event-name">
            <?php echo htmlspecialchars($row['EventName']); ?>
            </div>
            <div class="event-date">
            <?php echo date("F j, Y", strtotime($row['EventDate'])); ?>
            </div>
        </div>

        <!-- view collapse -->
        <a class="view" data-bs-toggle="collapse" href="#collapse<?php echo $row['EventID']; ?>" role="button" aria-expanded="false" aria-controls="collapse<?php echo $row['EventID']; ?>">
            View details
        </a>
        </div>

        <!-- hidden details before 'view'is clicked -->
        <div class="collapse " id="collapse<?php echo $row['EventID']; ?>">
        <div class="collapse-inner">
            <div><strong>Venue:</strong> <?php echo htmlspecialchars($row['EventVenueName']); ?></div>
            <div><strong>Cost:</strong> Php <?php echo number_format($row['EventCost'], 2); ?></div>
            <div><strong>Participants:</strong> <?php echo $row['NumberOfParticipants']; ?></div>
            <div><strong>Organized by: </strong><?php echo !empty($row['Organizers']) ? htmlspecialchars($row['Organizers']) : 'None'; ?> </div>

            <div class="mt-2">
            <!-- update button -->
            <a href="update_event.php?id=<?php echo $row['EventID']; ?>" class="text-primary">Update</a> |

            <!-- delete button -->
            <a href="delete_event.php?id=<?php echo $row['EventID']; ?>" 
            onclick= "return confirm('Are you sure you want to delete this event?');" 
            class="text-danger">
                Delete
            </a>
            </div>
        </div>
        </div>

    </div>
    <?php endwhile; ?>
    
  </div>

<!-- Pop-up if insert/add event is successful -->
 <?php
    if (isset($_GET['insert_msg'])) {
        $new_event_message = htmlspecialchars($_GET['insert_msg']);
        echo "<script>
            alert('$new_event_message');
            
            const cleanURL = window.location.origin + window.location.pathname;

            history.replaceState({}, document.title, cleanURL);
        </script>";
    }
 ?>
<!-- pop-up if event update is successful -->
 <?php
if (isset($_GET['update_msg'])) {
    $update_message = htmlspecialchars($_GET['update_msg']);
    echo "<script>
        alert('$update_message');
        
        const cleanURL = window.location.origin + window.location.pathname;

        history.replaceState({}, document.title, cleanURL);
    </script>";
}
?>
<!-- pop-up if event deletion is successful -->
<?php
if (isset($_GET['delete_msg'])) {
    $delete_message = htmlspecialchars($_GET['delete_msg']);
    echo "<script>
        alert('$delete_message');
        
        const cleanURL = window.location.origin + window.location.pathname;
        
        history.replaceState({}, document.title, cleanURL);
    </script>";
}
?>
</div>
<!-- modal -->
 <div class = "modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class ="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add New Event</h5>
    
            </div>
            <form action="add_event.php" method="POST" onsubmit="return confirmSaveEvent();">
            <div class="modal-body">
               
                    <div class="form-group">
                        <label for="e_name">Event Name</label>
                        <input type="text" id="e_name" name="e_name" class="form-control" required maxlength="150">

                        <small id="eventNameLimitMsg" class="text-danger" style="display:none;">
                            You have reached the maximum length for the Event Name
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="e_date">Event Date</label>
                        <input type="date" name="e_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="e_cost">Event Cost (Php)</label>
                        <input type="text"  id="e_cost" inputmode="decimal" name="e_cost" class="form-control" required  
                        pattern="^\d{1,6}(\.\d{1,2})?$" title="Enter a valid amount (0 to 999999.99)"  maxlength="9" placeholder="e.g., 6700.75">
                    </div>

                    <div class="form-group">
                        <label for="e_venue">Event Venue</label>
                        <select name="e_venue" id="e_venue" class="form-select" required onchange="toggleNewVenueField()">
                            <option value="">Select the Venue for the Event</option>
                            <?php while ($venue = mysqli_fetch_assoc($venue_result)): ?>
                            <option value="<?php echo $venue['EventVenueID']; ?>"><?php echo htmlspecialchars($venue['EventVenueName']); ?>
                            </option>
                            <?php endwhile; ?>
                            <option value="new" style="strong;">+ Add New Venue</option>
                        </select>
                        
                    </div>

                    <!-- user option to add new venue -->
                     <div class="form-group" id="newVenueField" style="display: none;">
                        <label for="new_event_venue">New Venue Name</label>
                        <input type="text" id="new_event_venue" name="new_event_venue" class="form-control" maxlength="150" placeholder="Enter name of new venue">

                        <small id="eventVenueNameLimitMsg" class="text-danger" style="display:none;">
                            You have reached the maximum length for the Event Venue Name
                        </small>
                    </div>
                
            </div>
          

            <div class="modal-footer">
                <button type ="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="add_event" class="btn btn-primary">Save Changes</button>
            </div>
            </form>

        </div>
    </div> 
 </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
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
