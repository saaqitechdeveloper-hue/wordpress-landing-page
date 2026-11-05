<?php
/**
 * SAAQi Tech - Admin Backend REST API Controller
 * Secure API endpoints for Admin Dashboard operations:
 * - list_inquiries: Retrieve leads & summary statistics
 * - get_lead: Single lead inspection
 * - update_lead: Update status & staff internal notes
 * - delete_lead: Delete inquiry
 * - save_settings: Update notification recipient emails
 * - test_smtp: Perform live SMTP delivery test via Hostinger
 * - export_csv: Stream formatted CSV export of inquiries
 *
 * Security:
 * - Session authentication via isAdminAuthenticated()
 * - CSRF token verification for all state-changing actions
 * - PDO prepared statements for 100% SQL injection immunity
 * - Strict input sanitization and validation
 */

// Strict error reporting in development; logs to error_log without breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');

// 1. Include core security and database modules
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/db.php';

// PHPMailer namespace imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Initialize secure session
initSecureSession();

// 2. Authentication Check
if (!isAdminAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'  => false,
        'error'    => 'Unauthorized',
        'message'  => 'Admin authentication required.',
        'redirect' => getAdminUrl('login.php')
    ]);
    exit;
}

// Connect to SQLite Database
$pdo = getDatabaseConnection();

// Parse Request Input (supports JSON payload, $_POST, and $_GET)
$rawInput = !empty($GLOBALS['__RAW_INPUT__']) ? $GLOBALS['__RAW_INPUT__'] : file_get_contents('php://input');
if (empty($rawInput) && php_sapi_name() === 'cli' && defined('STDIN')) {
    $read = [STDIN];
    $write = null;
    $except = null;
    if (@stream_select($read, $write, $except, 0, 20000) > 0) {
        $rawInput = stream_get_contents(STDIN);
    }
}
$jsonInput = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $jsonInput = $decoded;
    }
}
$input = array_merge($_GET, $_POST, $jsonInput);

// Determine requested action
$action = $_GET['action'] ?? $input['action'] ?? '';

// Extract CSRF token from input, POST, headers, or GET
$csrfToken = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_GET['csrf_token'] ?? '';

// Helper function to enforce CSRF validation
function enforceCsrf($token) {
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired CSRF token. Please refresh the page and try again.'
        ]);
        exit;
    }
}

// 3. Handle Actions
switch ($action) {

    // -------------------------------------------------------------------------
    // ACTION: list_inquiries
    // Query all leads ordered by newest first, and calculate summary statistics
    // -------------------------------------------------------------------------
    case 'list_inquiries':
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stmt = $pdo->prepare("SELECT * FROM inquiries ORDER BY id DESC");
            $stmt->execute();
            $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compute statistics
            $totalCount     = count($inquiries);
            $newCount       = 0;
            $convertedCount = 0;
            $todayCount     = 0;
            $todayDate      = date('Y-m-d');

            foreach ($inquiries as $inq) {
                $status = strtolower(trim($inq['status'] ?? ''));
                if ($status === 'new') {
                    $newCount++;
                } elseif ($status === 'converted') {
                    $convertedCount++;
                }

                $createdAt = $inq['created_at'] ?? '';
                $itemDate = !empty($createdAt) ? substr($createdAt, 0, 10) : '';
                if ($itemDate === $todayDate) {
                    $todayCount++;
                }
            }

            $stats = [
                'total'               => $totalCount,
                'today'               => $todayCount,
                'new'                 => $newCount,
                'converted'           => $convertedCount,
                'total_inquiries'     => $totalCount,
                'today_inquiries'     => $todayCount,
                'new_inquiries'       => $newCount,
                'converted_inquiries' => $convertedCount
            ];

            echo json_encode([
                'success'   => true,
                'inquiries' => $inquiries,
                'stats'     => $stats
            ]);
            exit;

        } catch (PDOException $e) {
            error_log('API list_inquiries Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve inquiries from database'
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // ACTION: get_lead
    // Retrieve a single inquiry by its ID
    // -------------------------------------------------------------------------
    case 'get_lead':
        header('Content-Type: application/json; charset=utf-8');
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or missing inquiry ID'
            ]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
            $stmt->execute([$id]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lead) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Inquiry not found'
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'lead'    => $lead
            ]);
            exit;

        } catch (PDOException $e) {
            error_log('API get_lead Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error retrieving lead details'
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // ACTION: update_lead
    // Update lead status ('new', 'contacted', 'converted', 'archived') and notes
    // -------------------------------------------------------------------------
    case 'update_lead':
        header('Content-Type: application/json; charset=utf-8');
        enforceCsrf($csrfToken);

        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or missing inquiry ID'
            ]);
            exit;
        }

        $allowedStatuses = ['new', 'contacted', 'converted', 'archived'];
        $status = strtolower(trim($input['status'] ?? ''));
        if (!in_array($status, $allowedStatuses, true)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid status. Permitted values: ' . implode(', ', $allowedStatuses)
            ]);
            exit;
        }

        $notes = trim($input['notes'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE inquiries SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $id]);

            echo json_encode([
                'success' => true,
                'message' => 'Updated'
            ]);
            exit;

        } catch (PDOException $e) {
            error_log('API update_lead Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error while updating inquiry'
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // ACTION: delete_lead
    // Delete an inquiry by ID
    // -------------------------------------------------------------------------
    case 'delete_lead':
        header('Content-Type: application/json; charset=utf-8');
        enforceCsrf($csrfToken);

        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or missing inquiry ID'
            ]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'success' => true,
                'message' => 'Deleted'
            ]);
            exit;

        } catch (PDOException $e) {
            error_log('API delete_lead Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error while deleting inquiry'
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // ACTION: save_settings
    // Validate comma-separated notification emails and save to settings table
    // -------------------------------------------------------------------------
    case 'save_settings':
        header('Content-Type: application/json; charset=utf-8');
        enforceCsrf($csrfToken);

        $rawEmails = $input['notification_emails'] ?? '';
        $emailParts = explode(',', $rawEmails);
        $cleanEmails = [];

        foreach ($emailParts as $part) {
            $trimmed = trim($part);
            if (!empty($trimmed) && filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                $cleanEmails[] = strtolower($trimmed);
            }
        }

        // Deduplicate
        $cleanEmails = array_values(array_unique($cleanEmails));

        if (empty($cleanEmails)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Please provide at least one valid email address.'
            ]);
            exit;
        }

        $emailString = implode(', ', $cleanEmails);

        try {
            // Check if notification_emails key exists
            $checkStmt = $pdo->prepare("SELECT setting_key FROM settings WHERE setting_key = 'notification_emails'");
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'notification_emails'");
                $stmt->execute([$emailString]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES ('notification_emails', ?, CURRENT_TIMESTAMP)");
                $stmt->execute([$emailString]);
            }

            echo json_encode([
                'success'             => true,
                'message'             => 'Settings saved',
                'notification_emails' => $emailString,
                'recipients'          => $cleanEmails
            ]);
            exit;

        } catch (PDOException $e) {
            error_log('API save_settings Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save settings into database'
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // ACTION: get_settings
    // Retrieve current administrative settings
    // -------------------------------------------------------------------------
    case 'get_settings':
        header('Content-Type: application/json; charset=utf-8');
        try {
            $recipients = getNotificationEmails($pdo);
            echo json_encode([
                'success'  => true,
                'settings' => [
                    'notification_emails' => implode(', ', $recipients),
                    'recipients'          => $recipients
                ]
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve settings'
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // ACTION: test_smtp
    // Use PHPMailer with Hostinger SMTP to send a test message to all recipients
    // -------------------------------------------------------------------------
    case 'test_smtp':
        header('Content-Type: application/json; charset=utf-8');
        enforceCsrf($csrfToken);

        $vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!file_exists($vendorAutoload)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'PHPMailer dependency not found at vendor/autoload.php'
            ]);
            exit;
        }
        require_once $vendorAutoload;

        // Fetch recipient emails from SQLite settings
        $recipients = getNotificationEmails($pdo);
        if (empty($recipients)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No recipient email addresses found in settings.'
            ]);
            exit;
        }

        $mail = new PHPMailer(true);

        try {
            // Hostinger SMTP configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'info@saaqitech.com';
            $mail->Password   = 'Saydev@1234';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;

            // Sender
            $mail->setFrom('info@saaqitech.com', 'SAAQi Tech Admin');

            // Add all configured recipients
            foreach ($recipients as $recipient) {
                $mail->addAddress($recipient);
            }

            $currentTimestamp = date('Y-m-d H:i:s T');
            $recipientHtmlList = implode(', ', array_map('htmlspecialchars', $recipients));

            // Content
            $mail->isHTML(true);
            $mail->Subject = '[Test] SAAQi Tech Admin Dashboard - SMTP Verification';
            $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #222; background: #0c0d12; padding: 25px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #161822; border: 1px solid #2d3142; border-radius: 12px; overflow: hidden; color: #e2e8f0;'>
                    <div style='background: linear-gradient(135deg, #ff1f3d 0%, #b80d24 100%); color: #ffffff; padding: 22px 28px;'>
                        <h2 style='margin: 0; font-size: 20px; font-weight: 700;'>SAAQi Tech - SMTP Test Verification</h2>
                        <p style='margin: 6px 0 0 0; font-size: 13px; color: #ffd6dc;'>Administrative Email Routing Diagnostic</p>
                    </div>
                    <div style='padding: 26px 28px;'>
                        <p style='font-size: 15px; line-height: 1.6; margin-top: 0; color: #cbd5e1;'>
                            This is an automated verification test dispatched from the <strong>SAAQi Tech Admin Dashboard</strong>.
                        </p>
                        <div style='background: #0d0e14; border-left: 4px solid #10b981; padding: 14px 18px; border-radius: 6px; margin: 20px 0;'>
                            <p style='margin: 0; font-size: 14px; color: #10b981; font-weight: bold;'>SMTP Handshake & TLS Connection: SUCCESS</p>
                        </div>
                        <table cellpadding='8' cellspacing='0' border='0' style='width:100%; font-size: 13px; color: #94a3b8; border-collapse: collapse;'>
                            <tr><td style='width: 35%; border-bottom: 1px solid #222637;'><strong>SMTP Host</strong></td><td style='color:#f8fafc; border-bottom: 1px solid #222637;'>smtp.hostinger.com:465 (SSL/TLS)</td></tr>
                            <tr><td style='border-bottom: 1px solid #222637;'><strong>Authenticated User</strong></td><td style='color:#f8fafc; border-bottom: 1px solid #222637;'>info@saaqitech.com</td></tr>
                            <tr><td style='border-bottom: 1px solid #222637;'><strong>Dispatched To</strong></td><td style='color:#f8fafc; border-bottom: 1px solid #222637;'>{$recipientHtmlList}</td></tr>
                            <tr><td><strong>Timestamp</strong></td><td style='color:#f8fafc;'>{$currentTimestamp}</td></tr>
                        </table>
                        <p style='margin-top: 24px; font-size: 12px; color: #64748b; line-height: 1.5;'>
                            If you received this message, prospective client lead notification emails will be reliably delivered to all configured addresses.
                        </p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->AltBody = "SAAQi Tech Admin Dashboard SMTP Verification\n\nSMTP Connection: SUCCESS\nHost: smtp.hostinger.com:465 (SSL)\nAuthenticated: info@saaqitech.com\nRecipients: " . implode(', ', $recipients) . "\nTimestamp: {$currentTimestamp}\n\nYour email notification system is working correctly.";

            $mail->send();

            echo json_encode([
                'success'    => true,
                'message'    => 'Test email sent successfully',
                'recipients' => $recipients
            ]);
            exit;

        } catch (Exception $e) {
            $errorMessage = $mail->ErrorInfo ?: $e->getMessage();
            error_log('API test_smtp Error: ' . $errorMessage);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'SMTP Test Failed: ' . $errorMessage
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // ACTION: export_csv
    // Stream CSV download of all leads with headers
    // -------------------------------------------------------------------------
    case 'export_csv':
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, phone, website_type, business, message, status, created_at FROM inquiries ORDER BY id DESC");
            $stmt->execute();
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $filename = 'saaqi_leads_' . date('Y-m-d_His') . '.csv';

            // Clean any existing output buffer to prevent corrupted CSV headers
            while (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

            $fp = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header Row
            fputcsv($fp, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Website Type',
                'Business',
                'Message',
                'Status',
                'Date'
            ]);

            // Stream each inquiry row
            foreach ($leads as $row) {
                fputcsv($fp, [
                    $row['id'] ?? '',
                    $row['name'] ?? '',
                    $row['email'] ?? '',
                    $row['phone'] ?? '',
                    $row['website_type'] ?? '',
                    $row['business'] ?? '',
                    $row['message'] ?? '',
                    $row['status'] ?? 'new',
                    $row['created_at'] ?? ''
                ]);
            }

            fclose($fp);
            exit;

        } catch (PDOException $e) {
            error_log('API export_csv Error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate CSV export'
            ]);
            exit;
        }

    // -------------------------------------------------------------------------
    // DEFAULT / UNKNOWN ACTION
    // -------------------------------------------------------------------------
    default:
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Unknown or missing action requested: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
        ]);
        exit;
}
