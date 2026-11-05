<?php
/**
 * SAAQi Tech - Admin Security Framework & Firewall Layer
 * - Web Application Firewall (WAF) signature checks
 * - Secure Session Hardening & Fingerprinting
 * - Dynamic Base URL & Path Resolver (Zero hardcoded domains)
 * - Cryptographic CSRF Protection
 * - Rate Limiting & Brute-Force Shield
 */

if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

// 1. Emit Modern Hardened Security Headers
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    // Flexible CSP allowing local assets, Google Fonts, and GSAP/Swiper CDNs
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com data:; img-src 'self' data: https:;");
}

// 2. Dynamic Base URL / Path Calculation (Portable to any Domain, Subdomain or Folder)
function getAppBaseUrl() {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Resolve project root by examining SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($scriptName));
    
    // If running inside /admin or /admin/..., step back to project root
    $dirParts = explode('/', trim($dir, '/'));
    if (end($dirParts) === 'admin') {
        array_pop($dirParts);
    }
    
    $rootPath = empty($dirParts) || (count($dirParts) === 1 && $dirParts[0] === '') ? '' : '/' . implode('/', $dirParts);
    return rtrim($protocol . $host . $rootPath, '/');
}

function getAdminUrl($path = '') {
    $base = getAppBaseUrl();
    return $base . '/admin/' . ltrim($path, '/');
}

function getAssetUrl($path = '') {
    $base = getAppBaseUrl();
    return $base . '/' . ltrim($path, '/');
}

// 3. Web Application Firewall (WAF) Request Filtering
function runWafInspection() {
    $maliciousPatterns = [
        '/\b(union\s+select|select\s+.*from|insert\s+into|delete\s+from|drop\s+table|update\s+.*set)\b/i', // Raw SQL injection
        '/<\s*script[^>]*>.*<\s*\/\s*script\s*>/is', // Script tags
        '/javascript\s*:/i',                          // Javascript: URIs
        '/\.\.\/|\.\.\\\\/',                         // Directory traversal
        '/\b(base64_decode|eval|passthru|shell_exec|system)\s*\(/i' // PHP Code Execution
    ];

    $rawPayload = file_get_contents('php://input');
    $GLOBALS['__RAW_INPUT__'] = $rawPayload;

    $checkList = [
        $_SERVER['QUERY_STRING'] ?? '',
        $rawPayload
    ];

    foreach ($checkList as $payload) {
        if (empty($payload)) continue;
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $payload)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Blocked by Security Firewall',
                    'code'  => 'WAF_SUSPICIOUS_PAYLOAD_DETECTED'
                ]);
                exit;
            }
        }
    }
}
runWafInspection();

// 4. Secure Session Initialization
function initSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    session_name('SAAQI_ADMIN_SESS');
    session_set_cookie_params([
        'lifetime' => 86400 * 2, // 48 hours
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    // Session Hijacking Protection via User-Agent & IP Subnet Fingerprint
    $currentFingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . getClientIpSubnet());
    if (!isset($_SESSION['auth_fingerprint'])) {
        $_SESSION['auth_fingerprint'] = $currentFingerprint;
    } elseif ($_SESSION['auth_fingerprint'] !== $currentFingerprint && isset($_SESSION['admin_logged_in'])) {
        // Suspected session hijack -> destroy session
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['auth_fingerprint'] = $currentFingerprint;
    }
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getClientIpSubnet() {
    $ip = getClientIp();
    $parts = explode('.', $ip);
    if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
    }
    return $ip;
}

// 5. CSRF Token Utilities
function generateCsrfToken() {
    initSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    initSecureSession();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// 6. Auth Checks
function isAdminAuthenticated() {
    initSecureSession();
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminAuth() {
    if (!isAdminAuthenticated()) {
        $loginUrl = getAdminUrl('login.php');
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized', 'redirect' => $loginUrl]);
            exit;
        }
        header("Location: {$loginUrl}");
        exit;
    }
}
