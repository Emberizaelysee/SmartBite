<?php
session_start();
require_once __DIR__ . '/../Backend/config/connection.php';

$token = trim($_GET['token'] ?? '');
$error = '';
$success = false;

if (empty($token)) {
    header("Location: /SmartBite/Frontend/forgot-password.html?error=invalid_token");
    exit();
}

$stmt = $conn->prepare("SELECT IdUser, reset_expires FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows !== 1) {
    // Token invalide ou expiré → redirect
    header("Location: /SmartBite/Frontend/forgot-password.html?error=expired_token");
    exit();
}

$user = $result->fetch_assoc();
$userId = $user['IdUser'];

// ── Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $password2) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $upd = $conn->prepare("UPDATE users SET UserPassword = ?, reset_token = NULL, reset_expires = NULL WHERE IdUser = ?");
        $upd->bind_param("si", $hashed, $userId);
        $upd->execute();
        $upd->close();

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SmartBite - Reset Password</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/auth.css">

  <style>
    .strength-bar {
      height: 4px;
      border-radius: 2px;
      background: #ddd;
      margin-top: 6px;
      overflow: hidden;
    }
    .strength-fill {
      height: 100%;
      width: 0%;
      border-radius: 2px;
      transition: width 0.3s, background 0.3s;
    }
    .strength-text {
      font-size: 12px;
      margin-top: 4px;
    }
    .success-icon {
      width: 70px;
      height: 70px;
      background: #e9f1ec;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0 auto 1rem;
    }
    .success-icon i {
      color: #16c451;
      font-size: 2rem;
    }
  </style>
</head>

<body>
<div class="container d-flex flex-column justify-content-center align-items-center min-vh-100">

  <!-- LOGO -->
  <a href="index.php" class="text-decoration-none">
    <div class="logo mb-4">
      <i class="fa-solid fa-utensils me-2 icon-green"></i>
      <span>Smart</span>Bite
    </div>
  </a>

  <!-- CARD -->
  <div class="card p-4 w-100" style="max-width: 400px;">

    <?php if ($success): ?>

      <!-- ── SUCCÈS ── -->
      <div class="text-center">
        <div class="success-icon">
          <i class="fa fa-check"></i>
        </div>
        <h5 class="fw-bold mt-2">Password updated!</h5>
        <p class="text-muted small mb-4">
          Your password has been reset successfully. You can now sign in.
        </p>
        <a href="signin.html" class="btn btn-green w-100">
          Sign In <i class="fa fa-arrow-right ms-1"></i>
        </a>
      </div>

    <?php else: ?>

      <!-- ── FORMULAIRE ── -->
      <a href="signin.html" class="forgot">
        <i class="fa fa-arrow-left"></i> Back to Sign In
      </a>

      <h5 class="mt-2 fw-bold">Reset your password</h5>
      <p class="text-muted small mb-4">Choose a new password for your account.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small">
          <i class="fa fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="resetForm">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <!-- New password -->
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-lock"></i></span>
            <input type="password" name="password" id="password"
                   class="form-control" placeholder="Min. 8 characters" required>
            <button type="button" class="input-group-text bg-white border-start-0"
                    onclick="togglePwd('password', this)">
              <i class="fa fa-eye text-muted"></i>
            </button>
          </div>
          <!-- Strength bar -->
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          <div class="strength-text text-muted" id="strengthText"></div>
        </div>

        <!-- Confirm password -->
        <div class="mb-4">
          <label class="form-label">Confirm Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-lock"></i></span>
            <input type="password" name="password2" id="password2"
                   class="form-control" placeholder="Repeat password" required>
            <button type="button" class="input-group-text bg-white border-start-0"
                    onclick="togglePwd('password2', this)">
              <i class="fa fa-eye text-muted"></i>
            </button>
          </div>
          <div class="small mt-1" id="matchMsg"></div>
        </div>

        <button type="submit" class="btn btn-green w-100" id="btn">
          Reset Password <i class="fa fa-shield-halved ms-1"></i>
        </button>
      </form>

    <?php endif; ?>

  </div>
</div>

<script>
// Toggle password visibility
function togglePwd(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

// Password strength
const pwdInput    = document.getElementById('password');
const strengthFill = document.getElementById('strengthFill');
const strengthText = document.getElementById('strengthText');

if (pwdInput) {
  pwdInput.addEventListener('input', () => {
    const v = pwdInput.value;
    let score = 0;
    if (v.length >= 8)            score++;
    if (/[A-Z]/.test(v))          score++;
    if (/[0-9]/.test(v))          score++;
    if (/[^A-Za-z0-9]/.test(v))   score++;

    const levels = [
      { w: '25%', color: '#e74c3c', label: 'Weak' },
      { w: '50%', color: '#e67e22', label: 'Fair' },
      { w: '75%', color: '#f1c40f', label: 'Good' },
      { w: '100%',color: '#16c451', label: 'Strong' },
    ];
    if (v.length === 0) {
      strengthFill.style.width = '0';
      strengthText.textContent = '';
    } else {
      const lvl = levels[score - 1] || levels[0];
      strengthFill.style.width      = lvl.w;
      strengthFill.style.background = lvl.color;
      strengthText.textContent      = lvl.label;
      strengthText.style.color      = lvl.color;
    }
  });
}

// Match indicator
const pwd2Input = document.getElementById('password2');
const matchMsg  = document.getElementById('matchMsg');

if (pwd2Input) {
  pwd2Input.addEventListener('input', () => {
    if (pwd2Input.value === '') {
      matchMsg.textContent = '';
    } else if (pwd2Input.value === pwdInput.value) {
      matchMsg.textContent = '✓ Passwords match';
      matchMsg.style.color = '#16c451';
    } else {
      matchMsg.textContent = '✗ Passwords do not match';
      matchMsg.style.color = '#e74c3c';
    }
  });
}

// Loading state on submit
document.getElementById('resetForm')?.addEventListener('submit', () => {
  document.getElementById('btn').innerHTML = 'Updating... <i class="fa fa-spinner fa-spin ms-1"></i>';
});
</script>

</body>
</html>