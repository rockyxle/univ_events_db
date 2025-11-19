<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__ . '/connect_to_db.php');

// Fetching all participants
$query = "
SELECT
   p.ParticipantID,
   p.ParticipantLastName,
   p.ParticipantFirstName,
   p.ParticipantInitial,
   p.ParticipantEmail,
   p.ParticipantContactNumber,
   p.ParticipantYearLevel,
   c.CourseID,
   c.CourseName
FROM
   Participants p
LEFT JOIN
   Courses c ON p.CourseID = c.CourseID
ORDER BY
   p.ParticipantLastName ASC
";
$result = mysqli_query($connection, $query);
if (!$result) {
   die('Query failed: ' . mysqli_error($connection));
}

// Fetching all the courses for dropdown
$course_query = "SELECT CourseID, CourseName FROM Courses ORDER BY CourseName ASC";
$course_result = mysqli_query($connection, $course_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Participants</title>
<link href="https://fonts.cdnfonts.com/css/garet" rel="stylesheet">
<link href="https://fonts.cdnfonts.com/css/arimo" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top bg-primary">
  <div class="container py-2 justify-content-between align-items-center">
    <div class="d-flex align-items-center">
      <!-- Back button -->
      <a href="javascript:history.back()" class="btn btn-outline-light btn-sm me-3">
        Back
      </a>
      <h2 class="mb-0 fw-bold text-white">Participants Dashboard</h2>
    </div>

    <!-- Search bar -->
    <div class="search-bar d-flex">
      <input type="text" placeholder="Search participants..." class="form-control">
      <button class="btn btn-outline-primary" type="submit">Search</button>
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
<div class="list-item-container p-4 rounded shadow-sm mt-3">
<?php while($row = mysqli_fetch_assoc($result)): ?>
 <div class="event-row mb-3 border p-3 rounded" role="article" aria-labelledby="p-<?php echo $row['ParticipantID']; ?>">
   <div class="d-flex justify-content-between align-items-center">
     <div>
       <div id="p-<?php echo $row['ParticipantID']; ?>" class="event-name">
         <?php echo htmlspecialchars($row['ParticipantLastName'] . ', ' . $row['ParticipantFirstName'] . ' ' . $row['ParticipantInitial']); ?>
       </div>
       <div class="event-date"><?php echo htmlspecialchars($row['ParticipantEmail']); ?></div>
     </div>
     <a class="view" data-bs-toggle="collapse" href="#collapse<?php echo $row['ParticipantID']; ?>" role="button" aria-expanded="false" aria-controls="collapse<?php echo $row['ParticipantID']; ?>">
       View Details
     </a>
   </div>

   <div class="collapse" id="collapse<?php echo $row['ParticipantID']; ?>">
     <div class="collape-inner">
       <div><strong>Contact Number:</strong> <?php echo htmlspecialchars($row['ParticipantContactNumber']); ?></div>
       <div><strong>Course:</strong> <?php echo htmlspecialchars($row['CourseName'] ?? 'N/A'); ?></div>
       <div><strong>Year Level:</strong> <?php echo htmlspecialchars($row['ParticipantYearLevel'] ?? 'N/A'); ?></div>
       <div class="mt-2">
         <a href="update_participant.php?id=<?php echo $row['ParticipantID']; ?>" class="text-primary">Update</a> |
         <a href="delete_participant.php?id=<?php echo $row['ParticipantID']; ?>" onclick="return confirm('Delete this participant?');" class="text-danger">Delete</a>
       </div>
     </div>
   </div>
 </div>
<?php endwhile; ?>
</div>

<!-- Success messages -->
<?php
$alerts = ['insert_msg', 'update_msg', 'delete_msg'];
foreach ($alerts as $msg) {
 if (isset($_GET[$msg])) {
   $message = htmlspecialchars($_GET[$msg]);
   echo "<script>
     alert('$message');
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
             <?php while ($course = mysqli_fetch_assoc($course_result)): ?>
               <option value="<?php echo $course['CourseID']; ?>"><?php echo htmlspecialchars($course['CourseName']); ?></option>
             <?php endwhile; ?>
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
</body>
</html>
