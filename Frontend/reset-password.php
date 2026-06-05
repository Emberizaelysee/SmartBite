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

    if ($password !== $password2) {
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
      <div id="passwordChecklist" class="small mt-1" style="display:none; line-height:1.9;">
         <div id="chk-len">⬜ Min. 8 characters</div>
         <div id="chk-lower">⬜ Lowercase letter (a-z)</div>
         <div id="chk-upper">⬜ Uppercase letter (A-Z)</div>
         <div id="chk-num">⬜ Number (0-9)</div>
         <div id="chk-spec">⬜ Special character (!@#$...)</div>
      </div>
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

function isStrongPassword() {
  const pass = document.getElementById('password').value;
  return pass.length >= 8 &&
    /[a-z]/.test(pass) && /[A-Z]/.test(pass) &&
    /[0-9]/.test(pass) && /[^a-zA-Z0-9]/.test(pass);
}

function checkPasswordStrength() {
  const pass = document.getElementById('password').value;
  const list = document.getElementById('passwordChecklist');

  if (pass === '') { list.style.display = 'none'; return; }
  list.style.display = 'block';

  const checks = {
    'chk-len':   pass.length >= 8,
    'chk-lower': /[a-z]/.test(pass),
    'chk-upper': /[A-Z]/.test(pass),
    'chk-num':   /[0-9]/.test(pass),
    'chk-spec':  /[^a-zA-Z0-9]/.test(pass),
  };

  const labels = {
    'chk-len':   'Min. 8 characters',
    'chk-lower': 'Lowercase letter (a-z)',
    'chk-upper': 'Uppercase letter (A-Z)',
    'chk-num':   'Number (0-9)',
    'chk-spec':  'Special character (!@#$...)',
  };

  const allOk = Object.values(checks).every(Boolean);

  if (allOk) {
    list.style.display = 'none';
    let strong = document.getElementById('strongMsg');
    if (!strong) {
      strong = document.createElement('div');
      strong.id = 'strongMsg';
      strong.className = 'small mt-1';
      list.parentNode.insertBefore(strong, list.nextSibling);
    }
    strong.style.color = '#16c451';
    strong.textContent = 'Strong password ✅';
    return;
  }

  const strong = document.getElementById('strongMsg');
  if (strong) strong.textContent = '';

  for (const [id, ok] of Object.entries(checks)) {
    const el = document.getElementById(id);
    el.textContent = (ok ? '✅ ' : '⬜ ') + labels[id];
    el.style.color = ok ? '#16c451' : '#888';
  }
}

document.getElementById('password')?.addEventListener('input', checkPasswordStrength);

function checkPasswordMatch() {
  const pass1    = document.getElementById('password').value;
  const pass2    = document.getElementById('password2').value;
  const matchDiv = document.getElementById('matchMsg');

  if (pass2 === '') { matchDiv.textContent = ''; return; }

  if (pass1 === pass2) {
    matchDiv.style.color = '#16c451';
    matchDiv.textContent = 'Passwords match ✅';
  } else {
    matchDiv.style.color = 'red';
    matchDiv.textContent = 'Passwords do not match ❌';
  }
}

document.getElementById('password2')?.addEventListener('input', checkPasswordMatch);

document.getElementById('resetForm')?.addEventListener('submit', (e) => {
  if (!isStrongPassword()) {
    e.preventDefault();
    checkPasswordStrength();
    return;
  }
  const pass1 = document.getElementById('password').value;
  const pass2 = document.getElementById('password2').value;
  if (pass1 !== pass2) {
    e.preventDefault();
    checkPasswordMatch();
    return;
  }
  document.getElementById('btn').innerHTML = 'Updating... <i class="fa fa-spinner fa-spin ms-1"></i>';
});
</script>
</body>
</html>