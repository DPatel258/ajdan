<?php
require __DIR__ . '/db.php';
session_start();
// Helper: Get user IP
function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    $ip = $_SERVER['REMOTE_ADDR'];

    // If IPv6 loopback (::1), convert to IPv4 loopback
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}

// Helper: Get device info
function getDeviceInfo()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $platform = 'Unknown';
    $deviceType = 'Desktop';

    if (preg_match('/android/i', $userAgent)) $platform = 'Android';
    elseif (preg_match('/iphone|ipad/i', $userAgent)) $platform = 'iOS';
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $platform = 'macOS';
    elseif (preg_match('/windows|win32/i', $userAgent)) $platform = 'Windows';
    elseif (preg_match('/linux/i', $userAgent)) $platform = 'Linux';

    if (preg_match('/mobile/i', $userAgent)) $deviceType = 'Mobile';
    elseif (preg_match('/tablet/i', $userAgent)) $deviceType = 'Tablet';

    return [
        'platform' => $platform,
        'deviceType' => $deviceType,
        'browserDetails' => $userAgent,
        'screenResolution' => $_POST['screen_resolution'] ?? null
    ];
}

// Helper: Get location from IP (using ip-api.com)
function getLocationFromIP($ip)
{
    if ($ip === '::1' || $ip === '127.0.0.1') {
        $ip = '8.8.8.8'; // Google DNS (just for testing)
    }
    $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");
    if ($response) {
        $data = json_decode($response, true);
        if ($data['status'] === 'success') {
            return [
                'country' => $data['country'] ?? null,
                'region' => $data['regionName'] ?? null,
                'city' => $data['city'] ?? null
            ];
        }
    }
    return ['country' => null, 'region' => null, 'city' => null];
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showError("Invalid form submission method.");
    exit;
}

// Capture metadata
$ip = getUserIP();
$location = getLocationFromIP($ip);
$deviceInfo = getDeviceInfo();
$timestamp = gmdate("Y-m-d H:i:s");
$sessionId = session_id();

// Get form data
$uniqueId = $_POST['unique_id'] ?? null;
$name = $_POST['name'] ?? null;
$phone = $_POST['mobile_number'] ?? null;

// Get dynamic question responses
$responses = [];
foreach ($_POST as $key => $value) {
    if (strpos($key, 'question_') === 0) {
        $responses[$key] = $value;
    }
}
// Convert responses to JSON
$responsesJson = json_encode($responses);


// Insert into DB
$stmt = $conn->prepare("INSERT INTO form_responses (
    unique_id, name, phone_number, responses,
    ip_address, country, region, city,
    platform, device_type, browser_details, screen_resolution,
    timestamp_utc, session_id
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "ssssssssssssss",
    $uniqueId,
    $name,
    $phone,
    $responsesJson,
    $ip,
    $location['country'],
    $location['region'],
    $location['city'],
    $deviceInfo['platform'],
    $deviceInfo['deviceType'],
    $deviceInfo['browserDetails'],
    $deviceInfo['screenResolution'],
    $timestamp,
    $sessionId
);
if ($stmt->execute()) {
    // ✅ Update response_submitted in users table
    $updateStmt = $conn->prepare("UPDATE users SET response_submitted = TRUE WHERE unique_id = ?");
    $updateStmt->bind_param("s", $uniqueId);
    $updateStmt->execute();
    $updateStmt->close();

    showSuccess();
} else {
    showError("Oops! Something went wrong while saving your response. Please try again.");
}


// Success page
function showSuccess()
{
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Thank You</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/x-icon" href="favicon.png">
        <link rel="stylesheet" href="style.css">
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    </head>
    <body class="message-body">
        <div class="card">
            <h1>🎉 Thank You!</h1>
            <span>Your response has been recorded successfully.</span>
            <a href="index.php" class="btn">Go Back</a>
        </div>
    </body>
    </html>';
}

// Error page
function showError($message)
{
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Error</title>
        <link rel="icon" type="image/x-icon" href="favicon.png">
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="message-body">
        <div class="card">
            <h1>⚠️ Error</h1>
            <span>' . htmlspecialchars($message) . '</span>
            <a href="index.php" class="btn">Try Again</a>
        </div>
    </body>
    </html>';
}
