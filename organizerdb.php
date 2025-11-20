<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'connect_to_db.php';

// ensure logged in as organizer
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer'){
    header("Location: index.php");
    exit;
}

$organizer_id = (int) $_SESSION['organizer_id'];

// total events (specific)
$result_events = $connection->query("
    SELECT COUNT(*) AS total
    FROM Events e
    INNER JOIN EventOrganizers eo ON e.EventID = eo.EventID
    WHERE eo.OrganizerID = {$organizer_id}
");
$total_events = ($result_events) ? $result_events->fetch_assoc()['total'] : 0;

// total participants (specific)
$result_participants = $connection->query("
    SELECT COUNT(*) AS total
    FROM EventParticipants ep
    INNER JOIN EventOrganizers eo ON ep.EventID = eo.EventID
    WHERE eo.OrganizerID = {$organizer_id}
");
$total_participants = ($result_participants) ? $result_participants->fetch_assoc()['total'] : 0;

//upcoming events
$result_upcoming = $connection->query("
    SELECT COUNT(*) AS total
    FROM Events e
    INNER JOIN EventOrganizers eo ON e.EventID = eo.EventID
    WHERE eo.OrganizerID = {$organizer_id}
      AND e.EventDate >= CURDATE()
");
$upcoming_events = ($result_upcoming) ? $result_upcoming->fetch_assoc()['total'] : 0;

// recent list
$updates_result = $connection->query("
    SELECT e.EventID, e.EventName, e.EventDate, v.EventVenueName
    FROM Events e
    INNER JOIN EventOrganizers eo ON e.EventID = eo.EventID
    LEFT JOIN EventVenues v ON e.EventVenueID = v.EventVenueID
    WHERE eo.OrganizerID = {$organizer_id}
    ORDER BY e.EventDate DESC
    LIMIT 5
");
$updates = [];
if ($updates_result) {
    while ($row = $updates_result->fetch_assoc()) {
        $updates[] = $row;
    }
}

// calendar
$calendar_result = $connection->query("
    SELECT e.EventName, e.EventDate
    FROM Events e
    INNER JOIN EventOrganizers eo ON e.EventID = eo.EventID
    WHERE eo.OrganizerID = {$organizer_id}
    ORDER BY e.EventDate ASC
");
$calendar_events = [];
if ($calendar_result) {
    while ($row = $calendar_result->fetch_assoc()) {
        $calendar_events[] = $row;
    }}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dbcalendar.css">
</head>
<body>

<header>
    <h2>Organizer Dashboard</h2>
    <nav class="navbar">
        <a href="events.php"><i class="fa fa-calendar-days"></i></a>
        <a href="participants.php"><i class="fa fa-users"></i></a>
        <a href="upcoming.php"><i class="fa fa-user-tie"></i></a>
    </nav>
        <a href="index.php" class="logout-btn" title="Logout">
            <i class="fa fa-right-from-bracket"></i>
        </a>
    </div>
</header>

<div class="main-content">
    <div class="greeting">hello, organizer 👋</div>
    <h1>here's your events overview</h1>

    <div class="card-container">
        <a href="eventsspec.php" class="card">
            <h3><i class="fa fa-calendar-days"></i> My Events</h3>
            <p><?php echo $total_events; ?></p>
        </a>
        <a href="participantsspec.php" class="card"> <!-- all pa din 'to, not fikltered-->
            <h3><i class="fa fa-users"></i> Participants</h3>
            <p><?php echo $total_participants; ?></p>
        </a>
        <a href="orgupcoming.php" class="card">
            <h3><i class="fa fa-clock"></i> Upcoming</h3>
            <p><?php echo $upcoming_events; ?></p>
        </a>
        <a href="events.php" class="card add-event">
        <h3><i class="fa fa-plus-circle"></i> Add Event</h3>
        <p>Create New</p>
        </a>
    </div>

    <div class="infinite-carousel">
        <div class="carousel-track">
            <img src="images/img1.jpg" alt="Event 1">
            <img src="images/img2.jpg" alt="Event 2">
            <img src="images/img3.jpg" alt="Event 3">
            <img src="images/img4.jpg" alt="Event 4">
            <img src="images/img5.jpg" alt="Event 5">
            <img src="images/img1.jpg" alt="Event 1">
            <img src="images/img2.jpg" alt="Event 2">
            <img src="images/img3.jpg" alt="Event 3">
            <img src="images/img4.jpg" alt="Event 4">
            <img src="images/img5.jpg" alt="Event 5">
        </div>
    </div>

    <div class="dashboard-bottom">
       <!-- Calendar -->
    <div class="calendar" style="flex:1; min-width:320px;">
        <h3><i class="fa fa-calendar-alt"></i> Event Calendar</h3>
        <div class="calendar-box">
            <div class="calendar-header">
                <button onclick="prevMonth()">‹</button>
                <span id="monthYear"></span>
                <button onclick="nextMonth()">›</button>
            </div>
            <div class="calendar-grid" id="calendar"></div>
            <ul class="event-list" id="eventList">
                <li>Select a date to view events</li>
            </ul>
        </div>
    </div>

        <!-- Upcoming / Recent Events -->
        <div class="updates" style="flex:1; min-width:320px;">
            <h3><i class="fa fa-bell"></i> Upcoming / Recent Events</h3>
            <ul>
                <?php 
                if(!empty($updates)){
                    foreach($updates as $update){
                        $date = date("M d, Y", strtotime($update['EventDate']));
                        $name = rawurlencode($update['EventName']);
                        $venue = rawurlencode($update['EventVenueName']);
                        echo "<li style='cursor:pointer;' onclick=\"showModal('{$name}', '{$date}', '{$venue}')\">📅 ".htmlspecialchars($update['EventName'])." <span style='color:#1f628e;font-weight:500;'>({$date})</span></li>";
                    }
                } else {
                    echo "<li>No recent events yet</li>";
                }
                ?>
            </ul>
            <!-- Event Info Modal -->
        <div id="eventModal" class="modal" style="display:none;">
            <div class="modal-content" style="position:relative; padding:20px; background:white; border-radius:12px; max-width:500px; margin:50px auto;">
                <span class="close" style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer;">&times;</span>
                <h2 id="modalEventName"></h2>
                <p id="modalEventDate" style="color:#555;"></p>
                <p id="modalEventVenue"></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>

<script>
    const eventData = <?php echo json_encode($calendar_events); ?>;

    let currentDate = new Date();

    function renderCalendar() {
        const monthYear = document.getElementById("monthYear");
        const calendar = document.getElementById("calendar");

        currentDate.setDate(1);

        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();

        monthYear.textContent = currentDate.toLocaleString("default", { month: "long" }) + " " + year;

        const firstDay = currentDate.getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();

        calendar.innerHTML = "";

        const dayNames = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
        dayNames.forEach(d => calendar.innerHTML += `<div class="day-name">${d}</div>`);

        for (let i = 0; i < firstDay; i++) {
            calendar.innerHTML += `<div></div>`;
        }

        for (let day = 1; day <= lastDate; day++) {
            const dateStr = `${year}-${String(month+1).padStart(2,"0")}-${String(day).padStart(2,"0")}`;

            const hasEvent = eventData.some(ev => ev.EventDate === dateStr);

            calendar.innerHTML += `
                <div class="calendar-day ${hasEvent ? 'has-event' : ''}" onclick="showEvents('${dateStr}')">
                    ${day}
                </div>
            `;
        }
    }

    function showEvents(date) {
        const list = document.getElementById("eventList");
        list.innerHTML = "";

        const events = eventData.filter(e => e.EventDate === date);

        if (events.length === 0) {
            list.innerHTML = "<li>No events on this day.</li>";
            return;
        }

        events.forEach(e => {
            list.innerHTML += `<li>📌 ${e.EventName}</li>`;
        });
    }

    function prevMonth() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    }

    function nextMonth() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    }

    renderCalendar();

    function showModal(name, date, venue) {
        name = decodeURIComponent(name);
        venue = decodeURIComponent(venue);
        document.getElementById('modalEventName').textContent = name;
        document.getElementById('modalEventDate').textContent = date;
        document.getElementById('modalEventVenue').textContent = venue;
        document.getElementById('eventModal').style.display = 'block';
    }

    // Close modal
    document.querySelector('#eventModal .close').onclick = function() {
        document.getElementById('eventModal').style.display = 'none';
    }
    window.onclick = function(event) {
        const modal = document.getElementById('eventModal');
        if (event.target === modal) modal.style.display = 'none';
    }
</script>
