<?php
session_start();
require_once __DIR__ . '/../Backend/config/connection.php';
require_once __DIR__ . '/../Backend/config/secrets.php';

// ─── ÉTAPE A : Pas encore de réponse Google → on redirige vers Google ───
if (!isset($_GET['code'])) {

    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
    ]);

    header("Location: https://accounts.google.com/o/oauth2/v2/auth?" . $params);
    exit();
}

// ─── ÉTAPE B : Google a renvoyé un code → on échange contre un token ───
$code = $_GET['code'];

// 1. Échanger le code contre un access token
$tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false,
    stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]),
    ]])
);

$tokenData = json_decode($tokenResponse, true);

if (!isset($tokenData['access_token'])) {
    header("Location: signin.html?error=google_failed");
    exit();
}

// 2. Récupérer les infos du user Google
$userInfoResponse = file_get_contents(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    false,
    stream_context_create(['http' => [
        'method' => 'GET',
        'header' => 'Authorization: Bearer ' . $tokenData['access_token'],
    ]])
);

$googleUser = json_decode($userInfoResponse, true);

$googleId = $googleUser['sub'];   // identifiant unique Google
$email    = $googleUser['email'];
$name     = $googleUser['name'];

// 3. Vérifier si l'email existe déjà en base
$stmt = $conn->prepare("SELECT IdUser, UserName, UserRole, IdGoogle FROM Users WHERE UserEmail = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // ── Utilisateur existant ──
    $user = $result->fetch_assoc();
    $stmt->close();

    // Si IdGoogle pas encore enregistré, on le sauvegarde
    if (empty($user['IdGoogle'])) {
        $upd = $conn->prepare("UPDATE Users SET IdGoogle = ? WHERE IdUser = ?");
        $upd->bind_param("si", $googleId, $user['IdUser']);
        $upd->execute();
        $upd->close();
    }

    $_SESSION['user_id']   = $user['IdUser'];
    $_SESSION['user_name'] = $user['UserName'];
    $_SESSION['user_role'] = $user['UserRole'];

} else {
    // ── Nouvel utilisateur → on crée le compte ──
    $stmt->close();

    $insert = $conn->prepare(
        "INSERT INTO Users (UserName, UserEmail, IdGoogle, UserRole) VALUES (?, ?, ?, 'user')"
    );
    $insert->bind_param("sss", $name, $email, $googleId);
    $insert->execute();
    $newId = $conn->insert_id;
    $insert->close();

    $_SESSION['user_id']   = $newId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = 'user';
}

$conn->close();
header("Location: index.html");
exit();
?>