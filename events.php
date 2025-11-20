<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');
include('check_user_role.php');

$limit = 15; // number of events per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) AS total FROM Events";
$count_result = mysqli_query($connection, $count_query);
$total_events = mysqli_fetch_assoc($count_result)['total'];

$total_pages = ceil($total_events / $limit);



// search and filter
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_venue = isset($_GET['filter_venue']) ? trim($_GET['filter_venue']) : '';
$min_cost = isset($_GET['min_cost']) && $_GET['min_cost'] !== '' ? floatval($_GET['min_cost']) : '';
$max_cost = isset($_GET['max_cost']) && $_GET['max_cost'] !== '' ? floatval($_GET['max_cost']) : '';
$min_part = isset($_GET['min_part']) && $_GET['min_part'] !== '' ? intval($_GET['min_part']) : '';
$max_part = isset($_GET['max_part']) && $_GET['max_part'] !== '' ? intval($_GET['max_part']) : '';

$filters_active = (!empty($filter_venue) || $min_cost !== '' || $max_cost !== '' || $min_part !== '' || $max_part !== '');

// where clauses
$where_clauses = [];
if (!empty($search_term)) {
    $safe_search = mysqli_real_escape_string($connection, $search_term);
    $where_clauses[] = "e.EventName LIKE '%$safe_search%'";
}
if (!empty($filter_venue)) {
    $safe_venue = mysqli_real_escape_string($connection, $filter_venue);
    $where_clauses[] = "e.EventVenueID = '$safe_venue'";
}
if ($min_cost !== '') $where_clauses[] = "e.EventCost >= $min_cost";
if ($max_cost !== '') $where_clauses[] = "e.EventCost <= $max_cost";

$sql_where = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : '';

// having calsues
$having_clauses = [];
if ($min_part !== '') $having_clauses[] = "NumberOfParticipants >= $min_part";
if ($max_part !== '') $having_clauses[] = "NumberOfParticipants <= $max_part";

$sql_having = count($having_clauses) > 0 ? "HAVING " . implode(' AND ', $having_clauses) : '';

// fetching main data
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
JOIN EventVenues v ON e.EventVenueID = v.EventVenueID
LEFT JOIN EventParticipants p ON e.EventID = p.EventID
$sql_where
GROUP BY e.EventID, e.EventName, v.EventVenueName, e.EventDate, e.EventCost
$sql_having
ORDER BY e.EventDate ASC
LIMIT $start, $limit
";
$result = mysqli_query($connection, $query);
if (!$result) die('Query failed: ' . mysqli_error($connection));

// fetching venue data
$venue_query = "SELECT EventVenueID, EventVenueName FROM EventVenues ORDER BY EventVenueName ASC";
$venue_result = mysqli_query($connection, $venue_query);
$venues_list = [];
while ($v = mysqli_fetch_assoc($venue_result)) {
    $venues_list[] = $v;
}
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
  <link rel="stylesheet" href="pagination.css">
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

<!-- navbar + search -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top bg-primary">
    <div class="container py-2 justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="<?php echo htmlspecialchars($backButton); ?>" class="btn btn-outline-light btn-sm me-3">Back</a>
            <h2 class="mb-0 fw-bold text-white">Events Dashboard</h2>
        </div>
        <form action="" method="GET" class="search-bar d-flex">
            <?php if(!empty($filter_venue)) echo '<input type="hidden" name="filter_venue" value="'.htmlspecialchars($filter_venue).'">'; ?>
            <?php if($min_cost !== '') echo '<input type="hidden" name="min_cost" value="'.htmlspecialchars($min_cost).'">'; ?>
            <?php if($max_cost !== '') echo '<input type="hidden" name="max_cost" value="'.htmlspecialchars($max_cost).'">'; ?>
            <?php if($min_part !== '') echo '<input type="hidden" name="min_part" value="'.htmlspecialchars($min_part).'">'; ?>
            <?php if($max_part !== '') echo '<input type="hidden" name="max_part" value="'.htmlspecialchars($max_part).'">'; ?>

            <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button class="btn btn-outline-light" type="submit">Search</button>
        </form>
    </div>
</nav>

<div class="container my-4">
     <!-- header + add event button -->
    <div class="events-header mx-auto">
        <div class="d-flex justify-content-center position-relative px-4 py-3">
            <h1 class="header-text mb-0 text-center">events</h1>
            <div class="position-absolute end-0 me-4 d-flex gap-2">
                <button class="btn <?php echo $filters_active ? 'btn-filter-active' : 'btn-light'; ?>" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">Filter</button>
                <button class="btn btn-light add-event-btn" data-bs-toggle="modal" data-bs-target="#exampleModal">+ Add Event</button>
            </div>
        </div>
    </div>

    <!-- FILTER OFFCANVAS -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas">
      <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title">Filter Options</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
      </div>
      <div class="offcanvas-body">
        <form action="" method="GET" onsubmit="return validateFilters()">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
            <div class="mb-4">
                <label class="form-label fw-bold">Venue</label>
                <select name="filter_venue" class="form-select">
                    <option value="">All Venues</option>
                    <?php foreach($venues_list as $v): ?>
                        <option value="<?php echo $v['EventVenueID']; ?>" <?php if($filter_venue == $v['EventVenueID']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($v['EventVenueName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Cost Range</label>
                <div class="d-flex gap-2">
                    <input type="number" name="min_cost" id="min_cost" class="form-control" placeholder="Min" value="<?php echo $min_cost; ?>">
                    <input type="number" name="max_cost" id="max_cost" class="form-control" placeholder="Max" value="<?php echo $max_cost; ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Participant Range</label>
                <div class="d-flex gap-2">
                    <input type="number" name="min_part" id="min_part" class="form-control" placeholder="Min" value="<?php echo $min_part; ?>">
                    <input type="number" name="max_part" id="max_part" class="form-control" placeholder="Max" value="<?php echo $max_part; ?>">
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <?php if($filters_active || !empty($search_term)): ?>
                    <a href="events.php" class="btn btn-outline-danger">Clear All</a>
                <?php endif; ?>
            </div>
        </form>
      </div>
    </div>

    <!-- events list -->
    <div class="list-item-container p-4 rounded shadow-sm mt-3">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="event-row" role="article" aria-labelledby="ev-<?php echo $row['EventID']; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div id="ev-<?php echo $row['EventID']; ?>" class="event-name"><?php echo htmlspecialchars($row['EventName']); ?></div>
                            <div class="event-date"><?php echo date("F j, Y", strtotime($row['EventDate'])); ?></div>
                        </div>
                        <a class="view" data-bs-toggle="collapse" href="#collapse<?php echo $row['EventID']; ?>" role="button" aria-expanded="false" aria-controls="collapse<?php echo $row['EventID']; ?>">View details</a>
                    </div>
                    <div class="collapse" id="collapse<?php echo $row['EventID']; ?>">
                        <div class="collapse-inner">
                            <div><strong>Venue:</strong> <?php echo htmlspecialchars($row['EventVenueName']); ?></div>
                            <div><strong>Cost:</strong> Php <?php echo number_format($row['EventCost'], 2); ?></div>
                            <div><strong>Participants:</strong> <?php echo $row['NumberOfParticipants']; ?></div>
                            <div class="mt-2">
                                <a href="update_event.php?id=<?php echo $row['EventID']; ?>" class="text-primary">Update</a> |
                                <a href="delete_event.php?id=<?php echo $row['EventID']; ?>" onclick="return confirm('Are you sure you want to delete this event?');" class="text-danger">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center p-5 text-muted">No events found matching your criteria.</div>
        <?php endif; ?>
    </div>
</div>

<!-- PAGINATION (this lang iaadd) -->
<nav>
  <ul class="pagination justify-content-center mt-4">

    <!-- Previous -->
    <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
      <a class="page-link" href="?page=<?php echo $page - 1; ?>"><</a>
    </li>

    <!-- Page numbers -->
    <?php for($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?php if($page == $i) echo 'active'; ?>">
        <a class="page-link" href="?page=<?php echo $i; ?>">
          <?php echo $i; ?>
        </a>
      </li>
    <?php endfor; ?>

    <!-- Next -->
    <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
      <a class="page-link" href="?page=<?php echo $page + 1; ?>">></a>
    </li>

  </ul>
</nav>

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
                            <?php foreach($venues_list as $venue): ?>
                                <option value="<?php echo $venue['EventVenueID']; ?>"><?php echo htmlspecialchars($venue['EventVenueName']); ?></option>
                            <?php endforeach; ?>
                            <option value="new">+ Add New Venue</option>
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
function validateFilters() {
    const minCost = document.getElementById('min_cost').value;
    const maxCost = document.getElementById('max_cost').value;
    const minPart = document.getElementById('min_part').value;
    const maxPart = document.getElementById('max_part').value;

    if(minCost !== '' && maxCost !== '' && parseFloat(minCost) > parseFloat(maxCost)) {
        alert("Minimum Cost cannot be greater than Maximum Cost."); return false;
    }
    if(minPart !== '' && maxPart !== '' && parseInt(minPart) > parseInt(maxPart)) {
        alert("Minimum Participants cannot be greater than Maximum Participants."); return false;
    }
    return true;
}
</script>
<script src="script.js"></script>
</body>
</html>
