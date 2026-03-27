<?php
include "connectDatabase.php" ; 

$token = $_GET['token'] ?? '';
$isValid = false;


// Verify Token
$stmt = $dbServer->prepare("SELECT id FROM pioUsers WHERE registrationToken = ? AND tokenExpiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $isValid = true;
    $userId = $row['id'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $isValid) {
    $password = $_POST['password'];

    // PHP Validation logic
    $uppercase = preg_match('@[A-Z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);

    if(!$uppercase || !$number || !$special || strlen($password) < 8) {
        $message = "<div class='alert alert-danger'>Password does not meet requirements.</div>";
    } else {
        $newPass = password_hash($password, PASSWORD_DEFAULT);
        
        $update = $dbServer->prepare("UPDATE pioUsers SET password_hash = ?, registrationToken = NULL, tokenExpiry = NULL WHERE id = ?");
        $update->bind_param("si", $newPass, $userId);
        $update->execute();
        
        header("Location: adminLogin.php?setup=success");
        exit;
    }
}
?>

<?php if ($isValid): ?>
   <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PIO | Set Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .hkc-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .hkc-header { color: #00A896; font-weight: 800; letter-spacing: 1px; }
        .btn-hkc {
            background-color: #FFD60A;
            color: #333;
            font-weight: bold;
            border: none;
            border-radius: 50px;
            transition: 0.3s;
        }
        .btn-hkc:hover { background-color: #e6c109; color: #000; }
        .form-control { border-radius: 10px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card hkc-card p-4 text-center">
                    <h1 class="hkc-header mb-2">PIO</h1>
                    <h4 class="text-muted mb-4">Set Your Secure Password</h4>
                    
                <div class="card hkc-card p-4 text-center">
    <h1 class="hkc-header mb-2">PIO</h1>
    <h4 class="text-muted mb-4">Set Your Secure Password</h4>
    
    <form method="POST" id="passwordForm">
        <div class="mb-3 text-start">
            <label class="form-label ps-1 small fw-bold">New Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autofocus>
        </div>

      <div class="mb-3 text-start">
    <label class="form-label ps-1 small fw-bold">New Password</label>
    <input type="password" name="password" id="password" 
           class="form-control" placeholder="••••••••" 
           required autofocus
           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
           title="Must contain at least 8 characters, including one uppercase letter, one number, and one special character.">
    <div class="form-text mt-1" style="font-size: 0.75rem;">
        Min. 8 chars, 1 uppercase, 1 number, 1 special char.
    </div>
</div>
        
        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-hkc py-2 shadow-sm">
                Activate Account
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('passwordForm').onsubmit = function(e) {
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const errorDiv = document.getElementById('passwordError');

    if (pass !== confirm) {
        e.preventDefault(); // Stop form from submitting
        errorDiv.style.display = 'block';
        return false;
    }
};
</script>
                    
                    <p class="mt-4 text-muted small">
                        PIO &copy; 2026
                    </p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
<?php else: ?>
    <p>This link is invalid or has expired. Please contact the PIO Admin.</p>
<?php endif; ?>