<?php
header("Content-Type: application/json");

// effacement du tampon regle Cannot modify header information - headers already sent
if (!ob_get_level()) {
    ob_start();
}

// recherche du fichier .env dans le projet (fonctionne quelque soit le chemin de SmartBite)
$findEnvFile = static function (string $startDir): ?string {
    $dir = realpath($startDir);
    while ($dir !== false) {
        $candidate = $dir . DIRECTORY_SEPARATOR . '.env';
        if (is_file($candidate)) {
            return $candidate;  
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }
    return null;
};

$envPath = $findEnvFile(__DIR__);
$env = $envPath ? parse_ini_file($envPath) : [];
$apiKey = trim($env['GEMINI_API_KEY'] ?? '', " \t\n\r\0\x0B\"'");

// eviter le forwarding des requetes anonymes
if (!$apiKey) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "error" => [
            "message" => "GEMINI_API_KEY is missing in .env"
        ]
    ]);
    exit;
}

$inputJSON = file_get_contents("php://input");
// le proxy attend le payload du frontend tel quel.
if (!$inputJSON) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        "error" => [
            "message" => "Missing request body"
        ]
    ]);
    exit;
}

$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';

$ch = curl_init($apiUrl);
// garder ce proxy intentionnellement mince: pas de transformation de payload, seulement le transport.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $apiKey,
    'Content-Length: ' . strlen($inputJSON),
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $inputJSON);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

ob_clean();
// afficher les erreurs de transport comme JSON API pour la coherence du frontend.
if ($response === false) {
    http_response_code(500);
    echo json_encode([
        "error" => [
            "message" => "cURL error: " . $error
        ]
    ]);
    exit;
}

http_response_code($httpCode);
echo $response;
?>