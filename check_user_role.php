<?php
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

switch ($_SESSION['role']) {
    case 'admin':
        $backButton= 'admindb.php';
        break;
    case 'organizer':
        $backButton = 'organizerdb.php';
        break;
    case 'participant':
        $backButton = 'participantdb.php';
        break;
    default:
        $backButton = 'index.php';
        break;
}

?>