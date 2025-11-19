<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');
include('check_user_role.php');


// fetching organizer data
$query = "
SELECT 
    o.OrganizerID,
    o.OrganizerName,
    o.OrganizerContactPerson,
    o.EmailOfContactPerson,
    o.NumberOfContactPerson,
    COUNT(eo.EventID) AS NumberOfEvents
FROM 
    Organizers o
LEFT JOIN 
    EventOrganizers eo ON o.OrganizerID = eo.OrganizerID
GROUP BY 
    o.OrganizerID, o.OrganizerName, o.OrganizerContactPerson, o.EmailOfContactPerson, o.NumberOfContactPerson
ORDER BY 
    o.OrganizerName ASC
";

$result = mysqli_query($connection, $query);
if(!$result) {
    die('Query failed: ' . mysqli_error($connection));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organizers</title>
  <link href="https://fonts.cdnfonts.com/css/garet" rel="stylesheet">
  <link href="https://fonts.cdnfonts.com/css/arimo" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>

<body>

<!-- navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top bg-primary">
    <div class="container py-2 justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <a href="<?php echo htmlspecialchars($backButton); ?>" class="btn btn-outline-light btn-sm me-3"">
          Back
       </a>
        <h2 class="mb-0 fw-bold text-white">Organizers Dashboard</h2>
      </div>

        <!-- search bar -->
        <div class="search-bar d-flex">
            <input type="text" placeholder="Search organizers...">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </div>
    </div>
</nav>

<div class="container my-4">
<!-- header -->
<div class="events-header mx-auto">
  <div class="d-flex justify-content-center position-relative px-4 py-3">
      <h1 class="header-text mb-0 text-center">organizers</h1>
      <button class="btn btn-light add-event-btn position-absolute end-0 me-4" data-bs-toggle="modal" data-bs-target="#exampleModal">
          + Add Organizer
      </button>
  </div>
</div>

<!-- organizer list -->
<div class="list-item-container p-4 rounded shadow-sm mt-3">
  <?php while($row = mysqli_fetch_assoc($result)): ?>
    <div class="event-row" role="article" aria-labelledby="org-<?php echo $row['OrganizerID']; ?>">

      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div id="org-<?php echo $row['OrganizerID']; ?>" class="event-name">
            <?php echo htmlspecialchars($row['OrganizerName']); ?>
          </div>
          <div class="event-date">
            <?php echo "Contact Person: " . htmlspecialchars($row['OrganizerContactPerson']); ?>
          </div>
        </div>

        <a class="view" data-bs-toggle="collapse" href="#collapse<?php echo $row['OrganizerID']; ?>" role="button" aria-expanded="false" aria-controls="collapse<?php echo $row['OrganizerID']; ?>">
          View details
        </a>
      </div>

      <!-- hidden details -->
      <div class="collapse" id="collapse<?php echo $row['OrganizerID']; ?>">
        <div class="collapse-inner">
          <div><strong>Organizer Email:</strong> <?php echo htmlspecialchars($row['EmailOfContactPerson']); ?></div>
          <div><strong>Contact Person Phone Number: </strong><?php echo htmlspecialchars($row['NumberOfContactPerson']); ?></div>
          <div><strong>Events Managed:</strong> <?php echo $row['NumberOfEvents']; ?></div>
          
          <div class="mt-2">
            <a href="update_organizer.php?id=<?php echo $row['OrganizerID']; ?>" class="text-primary">Update</a> |
            <a href="delete_organizer.php?id=<?php echo $row['OrganizerID']; ?>" 
               onclick="return confirm('Are you sure you want to delete this organizer?');" 
               class="text-danger">Delete</a>
          </div>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>
  </div>

<!-- popup message for insert, update, and delete -->
<?php
$alerts = ['insert_msg', 'update_msg', 'delete_msg'];
foreach ($alerts as $msg) {
    if (isset($_GET[$msg])) {
        $message = htmlspecialchars($_GET[$msg]);
        echo "<script>
            alert('$message');
            const cleanURL = window.location.origin + window.location.pathname;
            history.replaceState({}, document.title, cleanURL);
        </script>";
    }
}
?>

<!-- modal for adding new organizer -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add New Organizer</h5>
        
      </div>

      <form action="add_organizer.php" method="POST" onsubmit="return confirmSaveOrg();">
        <div class="modal-body">
          
          <div class="form-group">
            <label for="o_name">Organizer Name</label>
            <input type="text" id= "o_name" name="o_name" class="form-control" required maxlength="50">
            <small id="orgNameLimitMsg" class="text-danger" style="display:none;">
                You have reached the maximum length for the Organizer Name
              </small>
          </div>

          <div class="form-group">
            <label for="o_contact_person">Contact Person Name</label>
            <input type="text" id="o_contact_person" name="o_contact_person" class="form-control" required maxlength="75">
            <small id="orgCPLimitMsg" class="text-danger" style="display:none;">
                You have reached the maximum length for the Contact Person Name
            </small>
          </div>

          <div class="form-group">
            <label for="o_email">Email</label>
            <input type="email" id="o_email" name="o_email" class="form-control" required maxlength="75"  placeholder="e.g., lebronorbel@gmail.com">
            <small id="orgEmailLimitMsg" class="text-danger" style="display:none;">
                You have reached the maximum length for the Email
            </small>
          </div>

          <div class="form-group">
            <label for="o_contact">Contact Number</label>
            <input type="tel" id = "o_contact" name="o_contact" pattern="^\d{11}$"
            class="form-control" required maxlength="11" placeholder="e.g., 09997234501">
            <small id="orgConNumLimitMsg" class="text-danger" style="display:none;">
                Maximum Contact Number Length is 11
            </small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="add_organizer" class="btn btn-primary">Save Changes</button>
        </div>
      </form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
