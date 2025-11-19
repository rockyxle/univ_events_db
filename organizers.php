<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');
include('check_user_role.php');

// fetch search & filter inputs
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_contact = isset($_GET['filter_contact']) ? trim($_GET['filter_contact']) : '';
$min_events = isset($_GET['min_events']) && $_GET['min_events'] !== '' ? intval($_GET['min_events']) : '';
$max_events = isset($_GET['max_events']) && $_GET['max_events'] !== '' ? intval($_GET['max_events']) : '';

$filters_active = (!empty($filter_contact) || $min_events !== '' || $max_events !== '');

// build WHERE clauses
$where_clauses = [];

if (!empty($search_term)) {
    $safe_search = mysqli_real_escape_string($connection, $search_term);
    $where_clauses[] = "(o.OrganizerName LIKE '%$safe_search%' OR o.EmailOfContactPerson LIKE '%$safe_search%')";
}

if (!empty($filter_contact)) {
    $safe_contact = mysqli_real_escape_string($connection, $filter_contact);
    $where_clauses[] = "o.OrganizerContactPerson LIKE '%$safe_contact%'";
}

$sql_where = '';
if (count($where_clauses) > 0) {
    $sql_where = "WHERE " . implode(' AND ', $where_clauses);
}

// build HAVING clauses for number of events
$having_clauses = [];
if ($min_events !== '') $having_clauses[] = "NumberOfEvents >= $min_events";
if ($max_events !== '') $having_clauses[] = "NumberOfEvents <= $max_events";

$sql_having = '';
if (count($having_clauses) > 0) {
    $sql_having = "HAVING " . implode(' AND ', $having_clauses);
}

// fetching organizer data with search & filters applied
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
$sql_where
GROUP BY 
    o.OrganizerID, o.OrganizerName, o.OrganizerContactPerson, o.EmailOfContactPerson, o.NumberOfContactPerson
$sql_having
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
  <style>
    .search-bar input {
        border-radius: 0.375rem;
        border: 1px solid #ced4da;
        padding: 0.375rem 0.75rem;
        margin-right: 5px;
    }
    .btn-filter-active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
  </style>
</head>

<body>

<!-- navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top bg-primary">
    <div class="container py-2 justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <a href="<?php echo htmlspecialchars($backButton); ?>" class="btn btn-outline-light btn-sm me-3">
          Back
        </a>
        <h2 class="mb-0 fw-bold text-white">Organizers Dashboard</h2>
      </div>

      <!-- search bar -->
      <form action="" method="GET" class="search-bar d-flex">
          <?php if(!empty($filter_contact)): ?>
              <input type="hidden" name="filter_contact" value="<?php echo htmlspecialchars($filter_contact); ?>">
          <?php endif; ?>
          <?php if($min_events !== ''): ?>
              <input type="hidden" name="min_events" value="<?php echo htmlspecialchars($min_events); ?>">
          <?php endif; ?>
          <?php if($max_events !== ''): ?>
              <input type="hidden" name="max_events" value="<?php echo htmlspecialchars($max_events); ?>">
          <?php endif; ?>

          <input type="text" name="search" placeholder="Search organizers..." value="<?php echo htmlspecialchars($search_term); ?>">
          <button class="btn btn-outline-light" type="submit">Search</button>
      </form>
    </div>
</nav>

<div class="container my-4">

<!-- header -->
<div class="events-header mx-auto">
  <div class="d-flex justify-content-center position-relative px-4 py-3">
      <h1 class="header-text mb-0 text-center">organizers</h1>
      
      <!-- filter + add organizer buttons -->
      <div class="position-absolute end-0 me-4 d-flex gap-2">
          <button class="btn <?php echo $filters_active ? 'btn-filter-active' : 'btn-light'; ?> border" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
             Filter
          </button>
          <button class="btn btn-light add-event-btn" data-bs-toggle="modal" data-bs-target="#exampleModal">
              + Add Organizer
          </button>
      </div>
  </div>
</div>

<!-- filter offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas">
  <div class="offcanvas-header bg-light border-bottom">
    <h5 class="offcanvas-title">Filter Options</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form action="" method="GET" onsubmit="return validateFilters()">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">

        <div class="mb-4">
            <label class="form-label fw-bold">Contact Person</label>
            <input type="text" name="filter_contact" class="form-control" placeholder="Enter name..." value="<?php echo htmlspecialchars($filter_contact); ?>">
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Events Managed Range</label>
            <div class="d-flex gap-2">
                <input type="number" name="min_events" id="min_events" class="form-control" placeholder="Min" value="<?php echo $min_events; ?>">
                <input type="number" name="max_events" id="max_events" class="form-control" placeholder="Max" value="<?php echo $max_events; ?>">
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <?php if($filters_active || !empty($search_term)): ?>
                <a href="organizers.php" class="btn btn-outline-danger">Clear All</a>
            <?php endif; ?>
        </div>
    </form>
  </div>
</div>

<!-- organizer list -->
<div class="list-item-container p-4 rounded shadow-sm mt-3">
  <?php if(mysqli_num_rows($result) > 0): ?>
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
  <?php else: ?>
    <div class="text-center p-5 text-muted">No organizers found matching your criteria.</div>
  <?php endif; ?>
</div>

<!-- popup messages for insert, update, delete -->
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
            <input type="email" id="o_email" name="o_email" class="form-control" required maxlength="75">
            <small id="orgEmailLimitMsg" class="text-danger" style="display:none;">
                You have reached the maximum length for the Email
            </small>
          </div>

          <div class="form-group">
            <label for="o_contact">Contact Number</label>
            <input type="tel" id="o_contact" name="o_contact" pattern="^\d{11}$" class="form-control" required maxlength="11">
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

<script>
  // validate event range filters
  function validateFilters() {
      const minEv = document.getElementById('min_events').value;
      const maxEv = document.getElementById('max_events').value;

      if (minEv !== '' && maxEv !== '') {
          if (parseInt(minEv) > parseInt(maxEv)) {
              alert("Minimum Events cannot be greater than Maximum Events.");
              return false; 
          }
      }
      return true; 
  }
</script>
<script src="script.js"></script>
</body>
</html>
