<?php
include "authCheckPIO.php"; 
restrictToAdmin(); 
include "connectDatabase.php"; 

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_invite'])) {
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $userName = trim($_POST['username']); 

    // 1. Generate a secure random token
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", strtotime('+48 hours')); 

    // 2. Updated SQL: Removed schoolID from columns and values
    $stmt = $dbServer->prepare("INSERT INTO pioUsers (userName, email, role, registrationToken, tokenExpiry) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt) {
        // Only 5 "s" markers now
        $stmt->bind_param("sssss", $userName, $email, $role, $token, $expiry);

        if ($stmt->execute()) {
            // 3. Create the invitation link
            $inviteLink = "https://admin.teacherjohn.org/setupPassword.php?token=" . $token;

            // 4. Send the Email (Mention of School ID removed)
            $subject = "Invitation to PIO Portal";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: PIO Portal <noreply@teacherjohn.org>" . "\r\n";

            $emailBody = "<html><body style='font-family:Arial;'>";
            $emailBody .= "<h2 style='color:#00A896;'>Welcome to PIO!</h2>";
            $emailBody .= "<p>You have been invited to the portal as a <strong>$role</strong>.</p>";
            $emailBody .= "<p>Please click the button below to set up your password and activate your account:</p>";
            $emailBody .= "<a href='$inviteLink' style='background:#FFD60A; padding:12px 25px; text-decoration:none; color:black; border-radius:50px; font-weight:bold; display:inline-block;'>Set Up Your Account</a>";
            $emailBody .= "</body></html>";

            if(mail($email, $subject, $emailBody, $headers)) {
                $message = "<div class='alert alert-success'>Invitation sent to $email successfully!</div>";
            } else {
                // Fallback if mail server fails
                $message = "<div class='alert alert-warning'>User added, but email failed. Link: <a href='$inviteLink'>$inviteLink</a></div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Error: Username or Email already exists in the system.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Database Error: " . $dbServer->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invite User | PIO Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow border-0 p-4" style="border-radius: 20px;">
            <h3 class="text-center mb-4" style="color: #00A896;">Invite New User</h3>
            
            <?php echo $message; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. Sophea" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">User Role</label>
                    <select name="role" class="form-select">
                        <option value="dataEntry">Data Entry</option>
                        <option value="viewer">View Only</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" name="btn_invite" class="btn btn-success w-100 rounded-pill py-2 fw-bold shadow-sm">
                    Send Invitation
                </button>
            </form>
            
            <div class="mt-4 text-center border-top pt-3">
                <a href="adminDashboard.php" class="text-muted small text-decoration-none">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>