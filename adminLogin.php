<?php
// TEMPORARY: Show all errors so we can fix the 500 crash
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); 
session_start();

// 1. Database connection
//include "connectDatabase.php";
 include "connectDatabase.php"; 

$error = ""; 

// 2. Login Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_login'])) {
    
    $userInput = trim($_POST['username'] ?? '');
    $passInput = trim($_POST['password'] ?? '');

    // Prepare query (assuming pioUsers table has schoolID)
    $stmt = $dbServer->prepare("SELECT id, userName, password_hash, role, schoolID FROM pioUsers WHERE userName = ? OR email = ?");
    $stmt->bind_param("ss", $userInput, $userInput);
    $stmt->execute();
    
    // WORKAROUND: Bind the columns directly to variables instead of using get_result()
    $stmt->bind_result($db_id, $db_userName, $db_password_hash, $db_role, $db_schoolID);
    
    if ($stmt->fetch()) {
        // Verify the secure hash using our bound password variable
        if (password_verify($passInput, $db_password_hash)) {
            
            // --- SET SESSION VARIABLES ---
            $_SESSION['user_id'] = $db_id;
            $_SESSION['username'] = $db_userName;
            $_SESSION['role'] = $db_role;
            $_SESSION['schoolID'] = $db_schoolID; 

            // --- REDIRECTION ---
            if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'dataEntry') {
                header("Location: adminDashboard.php");
                exit;
            } 

        } else {
            $error = "Incorrect user name / password.";
        }
    } else {
        $error = "User / password not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .login-card { border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: none; }
        .login-header { background: #00A896; color: white; border-radius: 15px 15px 0 0; padding: 20px; }
        .btn-login { background-color: #02C39A; color: white; border-radius: 50px; font-weight: bold; }
        .btn-login:hover { background-color: #00A896; color: white; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card login-card" style="width: 100%; max-width: 400px;">
        <div class="login-header text-center">
            <h3 class="mb-0">Admin Access</h3>
        </div>
        <div class="card-body p-4 text-center">
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3 text-start">
                    <label class="form-label ms-3 small fw-bold">Username or Email</label>
                    <input type="text" name="username" class="form-control px-4" placeholder="Enter details" required>
                </div>
                
                <div class="mb-4 text-start">
                    <label class="form-label ms-3 small fw-bold">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="passwordField" class="form-control px-4" style="border-radius: 50px 0 0 50px;" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-radius: 0 50px 50px 0;">👁️</button>
                    </div>
                    <div class="mt-2 text-end">
                        <a href="forgotPassword.php" class="text-decoration-none small" style="color: #00A896;">Reset password</a>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" name="btn_login" class="btn btn-login">Login to Dashboard</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#passwordField');
    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁️' : '🙈';
    });
</script>

</body>
</html>