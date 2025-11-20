<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connect_to_db.php');

// get organizer ID from URL
if (!isset($_GET['id'])) {
    die('No organizer found');
}

$organizer_id = intval($_GET['id']);

// fetch organizer data
$organizer_query = "SELECT * FROM Organizers WHERE OrganizerID = $organizer_id";
$organizer_result = mysqli_query($connection, $organizer_query);

if (!$organizer_result || mysqli_num_rows($organizer_result) === 0) {
    die('Organizer not found');
}

$organizer = mysqli_fetch_assoc($organizer_result);

// handle form submission
if (isset($_POST['update_organizer'])) {
    $name = trim($_POST['o_name']);
    $contact_person = trim($_POST['o_contact_person']);
    $email = trim($_POST['o_email']);
    $contact_number = trim($_POST['o_contact']);

    // update query
    $update_query = "
        UPDATE Organizers
        SET OrganizerName='$name',
            OrganizerContactPerson='$contact_person',
            EmailOfContactPerson='$email',
            NumberOfContactPerson='$contact_number'
        WHERE OrganizerID=$organizer_id
    ";

    if (mysqli_query($connection, $update_query)) {
        header("Location: organizers.php?update_msg=" . urlencode("Organizer updated successfully"));
        exit;
    } else {
        die('Error updating organizer: ' . mysqli_error($connection));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Organizer</title>
<link href="https://fonts.cdnfonts.com/css/garet" rel="stylesheet">
<link href="https://fonts.cdnfonts.com/css/arimo" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="mx-auto">
    <div class="d-flex justify-content-center mt-3">
        <h1 class="header-update-text">Update Organizer</h1>
    </div>
    <div class="container mt-5">
        <form method="POST">
            <div class="form-group mb-3">
                <label for="o_name">Organizer Name</label>
                <input type="text" id= "o_name" name="o_name" maxlength="50" class="form-control" 
                required value="<?php echo htmlspecialchars($organizer['OrganizerName']); ?>">
                <small id="orgNameLimitMsg" class="text-danger" style="display:none;">
                    You have reached the maximum length for the Organizer Name
                </small>
            </div>

            <div class="form-group mb-3">
                <label for="o_contact_person">Contact Person Name</label>
                <input type="text"  id="o_contact_person" name="o_contact_person" class="form-control" maxlength="75" 
                required value="<?php echo htmlspecialchars($organizer['OrganizerContactPerson']); ?>">
                <small id="orgCPLimitMsg" class="text-danger" style="display:none;">
                    You have reached the maximum length for the Contact Person Name
                </small>
            </div>

            <div class="form-group mb-3">
                <label for="o_email">Email</label>
                <input type="email" id="o_email" name="o_email" class="form-control" maxlength="75"
                required value="<?php echo htmlspecialchars($organizer['EmailOfContactPerson']); ?>">
                <small id="orgEmailLimitMsg" class="text-danger" style="display:none;">
                    You have reached the maximum length for the Email
                </small>
            </div>

            <div class="form-group mb-3">
                <label for="o_contact">Contact Number</label> 
                <input type="tel" id="o_contact" name="o_contact" class="form-control" maxlength="11" pattern="^\d{11}$"
                required value="<?php echo htmlspecialchars($organizer['NumberOfContactPerson']); ?>">
                <small id="orgConNumLimitMsg" class="text-danger" style="display:none;">
                    Maximum Contact Number Length is 11
                </small>
            </div>

            <button type="submit" name="update_organizer" class="btn btn-primary">Update Organizer</button>
            <a href="organizers.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<script src="script.js"></script>
</body>
</html>