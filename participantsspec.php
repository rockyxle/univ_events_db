<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__ . '/connect_to_db.php'); // adjust path if needed

// --- role check ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    header("Location: index.php");
    exit;
}

// ensure organizer_id exists and is integer
$organizer_id = isset($_SESSION['organizer_id']) ? (int) $_SESSION['organizer_id'] : 0;
if ($organizer_id <= 0) {
    // session not properly set; redirect to login
    header("Location: index.php");
    exit;
}

//pagination
$limit = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $limit;

// total distint participants
$count_query = "
SELECT COUNT(DISTINCT p.ParticipantID) AS total
FROM Participants p
JOIN EventParticipants ep ON p.ParticipantID = ep.ParticipantID
JOIN Events e ON ep.EventID = e.EventID
JOIN EventOrganizers eo ON e.EventID = eo.EventID
WHERE eo.OrganizerID = {$organizer_id}
";
$count_result = mysqli_query($connection, $count_query);
if (!$count_result) {
    die("Count query failed: " . mysqli_error($connection));
}
$total_rows = (int) mysqli_fetch_assoc($count_result)['total'];
$total_pages = ($total_rows > 0) ? (int) ceil($total_rows / $limit) : 1;
if ($page > $total_pages) $page = $total_pages;

//course list
$course_sql = "SELECT CourseID, CourseName FROM Courses ORDER BY CourseName ASC";
$course_result = mysqli_query($connection, $course_sql);
if (!$course_result) {
    die("Course query failed: " . mysqli_error($connection));
}

//participant list
$participants_query = "
SELECT DISTINCT
    p.ParticipantID,
    p.ParticipantLastName,
    p.ParticipantFirstName,
    p.ParticipantInitial,
    p.ParticipantEmail,
    p.ParticipantContactNumber,
    c.CourseID,
    c.CourseName
FROM Participants p
JOIN EventParticipants ep ON p.ParticipantID = ep.ParticipantID
JOIN Events e ON ep.EventID = e.EventID
JOIN EventOrganizers eo ON e.EventID = eo.EventID
LEFT JOIN Courses c ON p.CourseID = c.CourseID
WHERE eo.OrganizerID = {$organizer_id}
ORDER BY p.ParticipantLastName ASC, p.ParticipantFirstName ASC
LIMIT {$start}, {$limit}
";
$result = mysqli_query($connection, $participants_query);
if (!$result) {
    die("Participants query failed: " . mysqli_error($connection));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Participants</title>

<link href="https://fonts.cdnfonts.com/css/garet" rel="stylesheet">
<link href="https://fonts.cdnfonts.com/css/arimo" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="pagination.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top bg-primary">
  <div class="container py-2 justify-content-between align-items-center">
    <div class="d-flex align-items-center">
      <a href="javascript:history.back()" class="btn btn-outline-light btn-sm me-3">Back</a>
      <h2 class="mb-0 fw-bold text-white">Participants Dashboard</h2>
    </div>

    <div class="search-bar d-flex">
      <input id="searchInput" type="text" placeholder="Search participants..." class="form-control">
      <button id="searchBtn" class="btn btn-outline-primary" type="button">Search</button>
    </div>
  </div>
</nav>

<!-- Header -->
<div class="events-header mx-auto">
  <div class="d-flex justify-content-center position-relative px-4 py-3">
      <h1 class="header-text mb-0 text-center">participants</h1>
      <button class="btn btn-light add-event-btn position-absolute end-0 me-4" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
          + Add Participant
      </button>
  </div>
</div>

<!-- List -->
<div class="container">
  <div class="list-item-container p-4 rounded shadow-sm mt-3">
    <?php if (mysqli_num_rows($result) === 0): ?>
      <div class="alert alert-info">No participants found for your events.</div>
    <?php else: ?>
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="event-row mb-3 border p-3 rounded" role="article" aria-labelledby="p-<?php echo (int)$row['ParticipantID']; ?>">
       <div class="d-flex justify-content-between align-items-center">
         <div>
           <div id="p-<?php echo (int)$row['ParticipantID']; ?>" class="event-name">
             <?php echo htmlspecialchars($row['ParticipantLastName'] . ', ' . $row['ParticipantFirstName'] . ' ' . ($row['ParticipantInitial'] ?? '')); ?>
           </div>
           <div class="event-date"><?php echo htmlspecialchars($row['ParticipantEmail']); ?></div>
         </div>
         <a class="view" data-bs-toggle="collapse" href="#collapse<?php echo (int)$row['ParticipantID']; ?>" role="button" aria-expanded="false" aria-controls="collapse<?php echo (int)$row['ParticipantID']; ?>">
           View Details
         </a>
       </div>

       <div class="collapse" id="collapse<?php echo (int)$row['ParticipantID']; ?>">
         <div class="collape-inner mt-3">
           <div><strong>Contact Number:</strong> <?php echo htmlspecialchars($row['ParticipantContactNumber']); ?></div>
           <div><strong>Course:</strong> <?php echo htmlspecialchars($row['CourseName'] ?? 'N/A'); ?></div>
           <div class="mt-2">
             <a href="update_participant.php?id=<?php echo (int)$row['ParticipantID']; ?>" class="text-primary">Update</a> |
             <a href="delete_participant.php?id=<?php echo (int)$row['ParticipantID']; ?>" onclick="return confirm('Delete this participant?');" class="text-danger">Delete</a>
           </div>
         </div>
       </div>
     </div>
     <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <nav>
    <ul class="pagination justify-content-center mt-4">
      <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
        <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">&lt;</a>
      </li>

      <?php
      // show a limited range of page numbers for usability
      $startPage = max(1, $page - 3);
      $endPage = min($total_pages, $page + 3);
      if ($startPage > 1) {
          echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
          if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
      }
      for ($i = $startPage; $i <= $endPage; $i++): ?>
        <li class="page-item <?php if($page == $i) echo 'active'; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor;
      if ($endPage < $total_pages) {
          if ($endPage < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
          echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
      }
      ?>

      <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
        <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>">&gt;</a>
      </li>
    </ul>
  </nav>
</div>

<!-- Alerts (insert/update/delete messages) -->
<?php
$alerts = ['insert_msg', 'update_msg', 'delete_msg'];
foreach ($alerts as $msg) {
 if (isset($_GET[$msg])) {
   $message = htmlspecialchars($_GET[$msg]);
   echo "<script>
     alert('{$message}');
     history.replaceState({}, document.title, window.location.pathname);
   </script>";
 }
}
?>

<!-- Add Participant Modal -->
<div class="modal fade" id="addParticipantModal" tabindex="-1" aria-labelledby="addParticipantLabel" aria-hidden="true">
 <div class="modal-dialog">
   <div class="modal-content">
     <form action="add_participant.php" method="POST">
       <div class="modal-header">
         <h5 class="modal-title" id="addParticipantLabel">Add New Participant</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       </div>
       <div class="modal-body">
         <div class="form-group mb-2">
           <label>Last Name</label>
           <input type="text" name="p_lastname" class="form-control" required>
         </div>
         <div class="form-group mb-2">
           <label>First Name</label>
           <input type="text" name="p_firstname" class="form-control" required>
         </div>
         <div class="form-group mb-2">
           <label>Initial</label>
           <input type="text" name="p_initial" class="form-control">
         </div>
         <div class="form-group mb-2">
           <label>Email</label>
           <input type="email" name="p_email" class="form-control" required>
         </div>
         <div class="form-group mb-2">
           <label>Contact Number</label>
           <input type="text" name="p_contact" class="form-control" required>
         </div>
         <div class="form-group mb-2">
           <label>Course</label>
           <select name="course_id" class="form-select">
             <option value="">Select Course</option>
             <?php
             // rewind course_result to start so we can loop again
             mysqli_data_seek($course_result, 0);
             while ($course = mysqli_fetch_assoc($course_result)): ?>
               <option value="<?php echo (int)$course['CourseID']; ?>"><?php echo htmlspecialchars($course['CourseName']); ?></option>
             <?php endwhile; ?>
           </select>
         </div>

         <!-- Optionally allow assigning to an event (only events for this organizer) -->
         <div class="form-group mb-2">
           <label>Assign to Event</label>
           <select name="event_id" class="form-select">
             <option value="">(optional) Select Event</option>
             <?php
             // fetch organizer's events for assignment
             $ev_q = "
               SELECT e.EventID, e.EventName, e.EventDate
               FROM Events e
               JOIN EventOrganizers eo ON e.EventID = eo.EventID
               WHERE eo.OrganizerID = {$organizer_id}
               ORDER BY e.EventDate ASC
             ";
             $ev_res = mysqli_query($connection, $ev_q);
             if ($ev_res) {
               while ($ev = mysqli_fetch_assoc($ev_res)) {
                 $label = htmlspecialchars($ev['EventName'] . ' — ' . date('M j, Y', strtotime($ev['EventDate'])));
                 echo '<option value="' . (int)$ev['EventID'] . '">' . $label . '</option>';
               }
             }
             ?>
           </select>
         </div>

       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
         <button type="submit" name="add_participant" class="btn btn-primary">Add Participant</button>
       </div>
     </form>
   </div>
 </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // basic client-side search (filter visible rows) - progressive enhancement
  document.getElementById('searchBtn').addEventListener('click', function(){
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    if (!q) return window.location.href = window.location.pathname; // no filter
    // Redirect to server side search (you can implement server side later). For now show alert.
    alert('Search requested: ' + q + '\nImplement server-side search if you want results from DB.');
  });
</script>
</body>
</html>
