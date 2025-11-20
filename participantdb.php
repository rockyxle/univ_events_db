<?php
include 'connect_to_db.php';

// Total registered events (general pa din)
$result_my_events = $connection->query("SELECT COUNT(*) as total FROM Participants");
$my_events = $result_my_events->fetch_assoc()['total'];

// Upcoming events (EventDate >= today)
$result_upcoming = $connection->query("SELECT COUNT(*) as total FROM Events WHERE EventDate >= CURDATE()");
$upcoming_events = $result_upcoming->fetch_assoc()['total'];

// Completed events (EventDate < today)
$result_completed = $connection->query("SELECT COUNT(*) as total FROM Events WHERE EventDate < CURDATE()");
$completed_events = $result_completed->fetch_assoc()['total'];

// Recent / Upcoming Events (with venue)
$updates_result = $connection->query("
    SELECT e.EventID, e.EventName, e.EventDate, v.EventVenueName
    FROM Events e
    LEFT JOIN EventVenues v ON e.EventVenueID = v.EventVenueID
    ORDER BY e.EventDate DESC
    LIMIT 5
");
$updates = [];
while ($row = $updates_result->fetch_assoc()) {
    $updates[] = $row;
}

// Total Organizers
$result_organizers = $connection->query("SELECT COUNT(*) as total FROM Organizers");
$total_organizers = $result_organizers->fetch_assoc()['total'];

// Calendar Events (for JS)
$calendar_result = $connection->query("SELECT EventName, EventDate FROM Events ORDER BY EventDate ASC");
$calendar_events = [];
while ($row = $calendar_result->fetch_assoc()) {
    $calendar_events[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Participant Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dbcalendar.css">
</head>
<body>

<header>
    <h2>Participant Dashboard</h2>
    <nav class="navbar">
        <a href="events.php"><i class="fa fa-ticket"></i></a>
        <a href="organizers.php"><i class="fa fa-user-tie"></i></a>
    </nav>
        <a href="index.php" class="logout-btn" title="Logout">
            <i class="fa fa-right-from-bracket"></i>
        </a>
    </div>

</header>

<div class="main-content">
    <div class="greeting">hello, participant 👋</div>
    <h1>here's your activity overview</h1>

    <div class="card-container">
        <a href="eventsgen.php" class="card"><!-- or events.php but general again ba?-->
            <h3><i class="fa fa-ticket"></i>My Events</h3>
            <p><?php echo $my_events; ?></p>
        </a>

        <a href="organizersgen.php" class="card">
            <h3><i class="fa fa-user-tie"></i> Organizers</h3>
            <p><?= $total_organizers ?></p>
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

