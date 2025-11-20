<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include(__DIR__ . '/connect_to_db.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
   die('Participant ID missing.');
}

$id = intval($_GET['id']);

// Fetching participant data
$query = "SELECT * FROM Participants WHERE ParticipantID = $id";
$result = mysqli_query($connection, $query);

if (!$result || mysqli_num_rows($result) == 0) {
   die('Participant not found.');
}

$row = mysqli_fetch_assoc($result);

// Fetching course options
$course_query = "SELECT CourseID, CourseName FROM Courses ORDER BY CourseName ASC";
$course_result = mysqli_query($connection, $course_query);

if (isset($_POST['update_participant'])) {
   $lastname = trim($_POST['p_lastname']);
   $firstname = trim($_POST['p_firstname']);
   $initial = trim($_POST['p_initial']);
   $email = trim($_POST['p_email']);
   $contact = trim($_POST['p_contact']);
   $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : 'NULL';

   $update_query = "
       UPDATE Participants
       SET
           ParticipantLastName = '$lastname',
           ParticipantFirstName = '$firstname',
           ParticipantInitial = '$initial',
           ParticipantEmail = '$email',
           ParticipantContactNumber = '$contact',
           CourseID = $course_id
       WHERE ParticipantID = $id
   ";

   if (mysqli_query($connection, $update_query)) {
       header("Location: participants.php?update_msg=Participant updated successfully");
       exit;
   } else {
       echo "<div class='alert alert-danger mt-3'>Error updating participant: " . mysqli_error($connection) . "</div>";
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Participant</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 p-4 bg-white shadow-sm rounded">
 <h2 class="mb-4">Update Participant</h2>
 <form action="update_participant.php?id=<?php echo $id; ?>" method="POST">

   <div class="form-group mb-3">
     <label>Last Name</label>
     <input type="text" id="p_lastname" name="p_lastname" class="form-control" maxlength="50"
     value="<?php echo htmlspecialchars($row['ParticipantLastName']); ?>" required>
     <small id="pLNameLimMsg" class="text-danger" style="display:none;">
        Maximum last name length reached
      </small>
   </div>

   <div class="form-group mb-3">
     <label>First Name</label>
     <input type="text" id="p_firstname" name="p_firstname" class="form-control" maxlength="50"
     value="<?php echo htmlspecialchars($row['ParticipantFirstName']); ?>" required>
     <small id="pFNameLimMsg class="text-danger" style="display:none;">
        Maximum first name length reached
      </small>
   </div>

   <div class="form-group mb-3">
     <label>Initial</label>
     <input type="text" id="p_initial" name="p_initial" class="form-control" maxlength="2"
     value="<?php echo htmlspecialchars($row['ParticipantInitial']); ?>">
     <small id="pIniLimMsg" class="text-danger" style="display:none;">
        Maximum middle initial length reached
      </small>
   </div>

   <div class="form-group mb-3">
     <label>Email</label>
     <input type="email" id="p_email" name="p_email" class="form-control" maxlength="75"
     value="<?php echo htmlspecialchars($row['ParticipantEmail']); ?>" required>
     <small id="pEmailLimMsg" class="text-danger" style="display:none;">
        Maximum email address length reached
      </small>
   </div>

   <div class="form-group mb-3">
     <label>Contact Number</label>
     <input type="tel" id="p_contact" name="p_contact" class="form-control" maxlength="11" pattern="^\d{11}$"
     value="<?php echo htmlspecialchars($row['ParticipantContactNumber']); ?>" required>
     <small id="pContactLimMsg" class="text-danger" style="display:none;">
        Maximum Contact Number Length is 11
      </small>
   </div>

   <div class="col-md-6 mb-3">
                <label class="form-label">Year Level</label>
                <select name="p_yearlevel" class="form-select" required>
                    <option value="1" <?php if($row['ParticipantYearLevel'] == 1) echo 'selected'; ?>>1st Year</option>
                    <option value="2" <?php if($row['ParticipantYearLevel'] == 2) echo 'selected'; ?>>2nd Year</option>
                    <option value="3" <?php if($row['ParticipantYearLevel'] == 3) echo 'selected'; ?>>3rd Year</option>
                    <option value="4" <?php if($row['ParticipantYearLevel'] == 4) echo 'selected'; ?>>4th Year</option>
                    <option value="5" <?php if($row['ParticipantYearLevel'] == 5) echo 'selected'; ?>>5th Year</option>
                    <option value="6" <?php if($row['ParticipantYearLevel'] == 6) echo 'selected'; ?>>6th Year</option>
                    <option value="7" <?php if($row['ParticipantYearLevel'] == 7) echo 'selected'; ?>>7th Year</option>
                </select>
            </div>
    </div>

   <div class="form-group mb-3">
     <label>Course</label>
     <select name="course_id" class="form-select">
       <option value="">Select Course</option>
       <?php while ($course = mysqli_fetch_assoc($course_result)): ?>
         <option value="<?php echo $course['CourseID']; ?>" <?php if ($row['CourseID'] == $course['CourseID']) echo 'selected'; ?>>
           <?php echo htmlspecialchars($course['CourseName']); ?>
         </option>
       <?php endwhile; ?>
     </select>
   </div>

   <button type="submit" name="update_participant" class="btn btn-primary">Update</button>
   <a href="participants.php" class="btn btn-secondary">Cancel</a>

 </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
