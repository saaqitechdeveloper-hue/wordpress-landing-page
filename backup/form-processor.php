<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// WordPress site details
$wordpress_site = 'https://pulsemarketing.cloud/algofort-crm';
$api_endpoint = '/wp-json/algofort/v1/submit';
$api_key = 'algofort_LlpCrQ9MdSy5wSN58K30lLyhWEiYmyWr'; // Change this!

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate data
if(!$input || empty($input['name']) || empty($input['email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Prepare data for WordPress
$wp_data = [
    'name' => sanitizeInput($input['name']),
    'email' => sanitizeInput($input['email']),
    'phone' => sanitizeInput($input['phone']),
    'project' => sanitizeInput($input['project']),
    'source_site' => $_SERVER['HTTP_HOST'] ?? 'custom-site'
];

// Send to WordPress
$ch = curl_init($wordpress_site . $api_endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($wp_data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $api_key
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if($http_code === 200) {
    echo json_encode(['success' => true, 'message' => 'Data sent to WordPress']);
} else {
    error_log('WordPress API Error: ' . $response);
    echo json_encode(['success' => false, 'message' => 'Failed to send to WordPress']);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>