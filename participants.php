<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');
include('check_user_role.php');

// fetch search & filter inputs
$search_term   = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_year   = isset($_GET['filter_year']) ? trim($_GET['filter_year']) : '';
$filter_course = isset($_GET['filter_course']) ? trim($_GET['filter_course']) : '';

$filters_active = (!empty($filter_year) || !empty($filter_course));

// build WHERE clauses
$where_clauses = [];

if (!empty($search_term)) {
    $safe_search = mysqli_real_escape_string($connection, $search_term);
    $where_clauses[] = "(p.ParticipantLastName LIKE '%$safe_search%' 
                         OR p.ParticipantFirstName LIKE '%$safe_search%' 
                         OR p.ParticipantEmail LIKE '%$safe_search%')";
}

if (!empty($filter_year)) {
    $safe_year = mysqli_real_escape_string($connection, $filter_year);
    $where_clauses[] = "p.ParticipantYearLevel = '$safe_year'";
}

if (!empty($filter_course)) {
    $safe_course = mysqli_real_escape_string($connection, $filter_course);
    $where_clauses[] = "p.CourseID = '$safe_course'";
}

$sql_where = '';
if (count($where_clauses) > 0) {
    $sql_where = "WHERE " . implode(' AND ', $where_clauses);
}

// fetch participants with search & filters applied
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
$sql_where
ORDER BY
   p.ParticipantLastName ASC
";

$result = mysqli_query($connection, $query);
if (!$result) die('Query failed: ' . mysqli_error($connection));

// fetch courses for filter dropdown
$course_query = "SELECT CourseID, CourseName FROM Courses ORDER BY CourseName ASC";
$course_result = mysqli_query($connection, $course_query);
$courses_list = [];
while ($c = mysqli_fetch_assoc($course_result)) $courses_list[] = $c;
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
      <a href="javascript:history.back()" class="btn btn-outline-light btn-sm me-3">Back</a>
      <h2 class="mb-0 fw-bold text-white">Participants Dashboard</h2>
    </div>

    <!-- search bar -->
    <form action="" method="GET" class="search-bar d-flex">
        <?php if(!empty($filter_year)): ?>
            <input type="hidden" name="filter_year" value="<?php echo htmlspecialchars($filter_year); ?>">
        <?php endif; ?>
        <?php if(!empty($filter_course)): ?>
            <input type="hidden" name="filter_course" value="<?php echo htmlspecialchars($filter_course); ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="Search participants..." value="<?php echo htmlspecialchars($search_term); ?>">
        <button class="btn btn-outline-light" type="submit">Search</button>
    </form>
  </div>
</nav>

<div class="container my-4">

<!-- header -->
<div class="events-header mx-auto">
  <div class="d-flex justify-content-center position-relative px-4 py-3">
      <h1 class="header-text mb-0 text-center">participants</h1>
      
      <!-- filter + add participant buttons -->
      <div class="position-absolute end-0 me-4 d-flex gap-2">
          <button class="btn <?php echo $filters_active ? 'btn-filter-active' : 'btn-light'; ?> border" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
             Filter
          </button>
          <button class="btn btn-light add-event-btn" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
              + Add Participant
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
    <form action="" method="GET">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">

        <div class="mb-4">
            <label class="form-label fw-bold">Year Level</label>
            <select name="filter_year" class="form-select">
                <option value="">All Year Levels</option>
                <?php for($i=1; $i<=7; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php if($filter_year == $i) echo 'selected'; ?>><?php echo $i; ?>th Year</option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Course</label>
            <select name="filter_course" class="form-select">
                <option value="">All Courses</option>
                <?php foreach($courses_list as $crs): ?>
                    <option value="<?php echo $crs['CourseID']; ?>" <?php if($filter_course == $crs['CourseID']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($crs['CourseName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <?php if($filters_active || !empty($search_term)): ?>
                <a href="participants.php" class="btn btn-outline-danger">Clear All</a>
            <?php endif; ?>
        </div>
    </form>
  </div>
</div>

<!-- participant list -->
<div class="list-item-container p-4 rounded shadow-sm mt-3">
<?php if(mysqli_num_rows($result) > 0): ?>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
     <div class="event-row" role="article" aria-labelledby="org-<?php echo $row['ParticipantID']; ?>">
       <div class="d-flex justify-content-between align-items-center">
         <div>
           <div class="event-name">
             <?php echo htmlspecialchars($row['ParticipantLastName'] . ', ' . $row['ParticipantFirstName'] . ' ' . $row['ParticipantInitial']); ?>
           </div>
           <div class="event-date"><?php echo htmlspecialchars($row['ParticipantEmail']); ?></div>
         </div>
         <a class="view" data-bs-toggle="collapse" href="#collapse<?php echo $row['ParticipantID']; ?>" role="button" aria-expanded="false" aria-controls="collapse<?php echo $row['ParticipantID'];?>">
           View Details
         </a>
       </div>

       <div class="collapse" id="collapse<?php echo $row['ParticipantID']; ?>">
         <div class="collapse-inner">
            <div class="row">
                <div><strong>Contact:</strong> <?php echo htmlspecialchars($row['ParticipantContactNumber']); ?></div>
                <div><strong>Year:</strong> <?php echo htmlspecialchars($row['ParticipantYearLevel']); ?></div>
                <div><strong>Course:</strong> <?php echo htmlspecialchars($row['CourseName'] ?? 'N/A'); ?></div>
            </div>
           <div class="mt-2">
             <a href="update_participant.php?id=<?php echo $row['ParticipantID']; ?>" class="text-primary">Update</a> | 
             <a href="delete_participant.php?id=<?php echo $row['ParticipantID']; ?>" onclick="return confirm('Delete this participant?');" class="text-danger">Delete</a>
           </div>
         </div>
       </div>
     </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="text-center p-5 text-muted">No participants found matching your criteria.</div>
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

<!-- modal for adding new participant -->
<div class="modal fade" id="addParticipantModal" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog">
   <div class="modal-content">
     <form action="add_participant.php" method="POST">
       <div class="modal-header">
         <h5 class="modal-title">Add New Participant</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
       </div>
       <div class="modal-body">
         <div class="row">
             <div class="col-md-5 mb-2">
               <label>Last Name</label>
               <input type="text" id = "p_lastname" name="p_lastname" class="form-control" required maxlength="50">
               <small id="pLNameLimMsg" class="text-danger" style="display:none;">
                  Maximum last name length reached
               </small>
             </div>
             <div class="col-md-5 mb-2">
               <label>First Name</label>
               <input type="text" id="first_name"name="p_firstname" class="form-control" required maxlength="50">
               <small id="pFNameLimMsg class="text-danger" style="display:none;">
                  Maximum first name length reached
               </small>
             </div>
             <div class="col-md-2 mb-2">
               <label>M.I.</label>
               <input type="text" id="p_initial"name="p_initial" class="form-control" maxlength="2">
               <small id="pIniLimMsg" class="text-danger" style="display:none;">
                  Maximum middle initial length reached
               </small>
             </div>
         </div>
         <div class="form-group mb-2">
           <label>Email</label>
           <input type="email" id= "p_email" name="p_email" class="form-control" required maxlength="75">
           <small id="pEmailLimMsg" class="text-danger" style="display:none;">
                  Maximum email address length reached
           </small>
         </div>
         <div class="row">
             <div class="col-md-6 mb-2">
               <label>Contact Number</label>
               <input type="tel" pattern="^\d{11}$" id="p_contact" name="p_contact" class="form-control" required maxlength="11">
               <small id="pContactLimMsg" class="text-danger" style="display:none;">
                  Maximum Contact Number Length is 11
               </small>
             </div>
             <div class="col-md-6 mb-2">
               <label>Year Level</label>
               <select name="p_yearlevel" class="form-select" required>
                   <option value="" disabled selected>Select Year</option>
                   <?php for($i=1; $i<=7; $i++): ?>
                       <option value="<?php echo $i; ?>"><?php echo $i; ?>th Year</option>
                   <?php endfor; ?>
               </select>
             </div>
         </div>
         <div class="form-group mb-2">
           <label>Course</label>
           <select name="course_id" class="form-select">
             <option value="">Select Course</option>
             <?php foreach($courses_list as $crs): ?>
               <option value="<?php echo $crs['CourseID']; ?>"><?php echo htmlspecialchars($crs['CourseName']); ?></option>
             <?php endforeach; ?>
           </select>
         </div>
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         <button type="submit" name="add_participant" class="btn btn-primary">Add Participant</button>
       </div>
     </form>
   </div>
 </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
