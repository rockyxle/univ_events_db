<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('connect_to_db.php'); // <-- make sure this exists

$error = '';

if(isset($_POST['login'])){
    $username = $_POST['username'];   // email for organizers
    $password = $_POST['password'];

    // ADMIN LOGIN
    if($username === "admin" && $password === "admin123"){
        $_SESSION['role'] = 'admin';
        header("Location: admindb.php");
        exit;
    }

    // ORGANIZER LOGIN (from organizers)
    $query = "
        SELECT * FROM Organizers
        WHERE EmailOfContactPerson = '$username'
        AND OrganizerPassword = '$password'
    ";

    $result = $connection->query($query);

    if($result && $result->num_rows === 1){
        $row = $result->fetch_assoc();
        $_SESSION['role'] = 'organizer';
        $_SESSION['organizer_id'] = $row['OrganizerID'];
        header("Location: organizerdb.php");
        exit;
    }

    // If none matched
    $error = "Invalid username or password!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #f9f9ff);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: rgba(255,255,255,0.90);
            padding: 40px;
            border-radius: 16px;
            width: 320px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #1f628e;
            margin-bottom: 30px;
        }

        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 12px;
            outline: none;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            background: #1f628e;
            color: white;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        button:hover {
            background: #2b7bb8;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .guest-btn {
            display: block;
            width: 92%;
            padding: 12px;
            text-align: center;
            background-color: white;
            color: #1f6280;
            border: 1px solid #1f6280;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            margin-top: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .guest-btn:hover {
            background-color: #eef3f8;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login</h2>

    <?php if($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Email / Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <a href="participantdb.php" class="guest-btn">Continue as Guest</a>
</div>

</body>
</html>
