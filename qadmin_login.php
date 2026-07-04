<?php
// qadmin_login.php
require_once "db.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $hash = hash('sha256', $pass);
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username=? AND password_hash=?");
    $stmt->bind_param("ss", $user, $hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $_SESSION['admin'] = $user;
        header("Location: qadmin.php");
        exit;
    } else {
        $err = "Invalid credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
/* Reset & Body */
* { box-sizing: border-box; margin:0; padding:0; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #4e54c8, #8f94fb);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Login Box */
.login-box {
    background: #fff;
    padding: 40px 30px;
    border-radius: 12px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    position: relative;
    animation: fadeIn 0.6s ease;
}

/* Title */
h2 {
    text-align: center;
    margin-bottom: 28px;
    font-size: 24px;
    color: #333;
}

/* Form Inputs */
label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #555;
}
input[type="text"], input[type="password"] {
    width: 100%;
    padding: 12px;
    margin-bottom: 16px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    transition: border 0.2s, box-shadow 0.2s;
}
input[type="text"]:focus, input[type="password"]:focus {
    border-color: #4e54c8;
    box-shadow: 0 0 5px rgba(78,84,200,0.4);
    outline: none;
}

/* Button */
button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    background: #4e54c8;
    color: #fff;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}
button:hover {
    background: #3b40a4;
}

/* Error Message */
.error {
    color: red;
    margin-bottom: 12px;
    text-align: center;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 480px){
    .login-box { padding: 30px 20px; }
}

/* Animation */
@keyframes fadeIn {
    0% { opacity:0; transform: translateY(-20px);}
    100% { opacity:1; transform: translateY(0);}
}
</style>
</head>
<body>
<div class="login-box">
    <h2>Admin Login</h2>
    <?php if(!empty($err)) echo "<div class='error'>$err</div>"; ?>
    <form method="post">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter username" required>
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
