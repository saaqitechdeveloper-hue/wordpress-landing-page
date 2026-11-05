<?php
/**
 * SAAQi Tech - Winter Sale Lead Processor & Notification Engine
 * - SQLite Inquiries Persistence (100% Prepared Statements)
 * - Dynamic Multi-Recipient Notification Routing from SQLite Settings
 * - Hostinger SMTP Dispatch via PHPMailer (SSL Port 465)
 * - High-End Luxury Dark Glass Responsive HTML Lead Notification
 */

// Output headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Respond to OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error reporting configuration: log errors, suppress output display to keep JSON intact
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 1. Include SQLite Database Framework & Security Layer
require_once __DIR__ . '/admin/config/db.php';

// 2. Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

// Sanitization Helper
function sanitizeInput($data) {
    if ($data === null || $data === '') return '';
    $data = trim((string)$data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Parse incoming payload (supports JSON body and standard POST form data)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input) || empty($input)) {
    if (!empty($_POST)) {
        $input = $_POST;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No data received. Please complete the form.'
        ]);
        exit;
    }
}

// Log incoming request
error_log('Winter Sale Form Data Received: ' . print_r($input, true));

// 2. Validate required fields (Name and Email required per specification)
if (empty($input['name']) || empty($input['email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill all required fields: Name and Email'
    ]);
    exit;
}

$rawEmail = trim((string)($input['email'] ?? ''));
if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

// Sanitize all inputs
$name         = sanitizeInput($input['name'] ?? '');
$email        = sanitizeInput($rawEmail);
$phone        = sanitizeInput($input['phone'] ?? '');
$business     = sanitizeInput($input['business'] ?? '');
$raw_website_type = sanitizeInput($input['website_type'] ?? '');

$valid_website_types = [
    'WordPress',
    'Custom WordPress',
    'WordPress + AI'
];
if (in_array($raw_website_type, $valid_website_types, true)) {
    $website_type = $raw_website_type;
} elseif (stripos($raw_website_type, 'AI') !== false) {
    $website_type = 'WordPress + AI';
} elseif (stripos($raw_website_type, 'Custom') !== false) {
    $website_type = 'Custom WordPress';
} elseif (!empty($raw_website_type)) {
    $website_type = 'WordPress';
} else {
    $website_type = 'WordPress';
}

$message      = sanitizeInput($input['message'] ?? $input['project'] ?? '');
$budget       = sanitizeInput($input['budget'] ?? '');
$project      = $message;

// Capture client IP and User-Agent
$ipAddress    = function_exists('getClientIp') ? getClientIp() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$userAgent    = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// 3. Connect to SQLite & 4. Insert lead into inquiries table
$leadId = 0;
$pdo = null;

try {
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO inquiries (name, email, phone, website_type, business, message, budget, ip_address, user_agent, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
    ");
    $stmt->execute([
        $name,
        $email,
        $phone,
        $website_type,
        $business,
        $message,
        $budget,
        $ipAddress,
        $userAgent
    ]);
    
    $leadId = (int)$pdo->lastInsertId();
    error_log("Winter Sale lead successfully saved to SQLite inquiries table with ID #{$leadId}");
} catch (Exception $e) {
    error_log("Database lead persistence error: " . $e->getMessage());
}

// 5. Fetch recipient notification emails dynamically from SQLite
$recipientEmails = [];
if ($pdo instanceof PDO) {
    try {
        $recipientEmails = getNotificationEmails($pdo);
    } catch (Exception $e) {
        error_log("Failed to fetch notification emails from settings: " . $e->getMessage());
    }
}
if (empty($recipientEmails)) {
    $recipientEmails = ['info@saaqitech.com', 'saaqitechdeveloper@gmail.com'];
}

// 8. Build Luxury Dark Glass Responsive HTML Email Notification
$cleanPhone       = preg_replace('/[^0-9]/', '', $phone);
$waLink           = !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}" : '#';
$waDisplay        = !empty($phone) ? $phone : 'Not provided';
$mailtoLink       = "mailto:{$email}?subject=" . rawurlencode("Re: Winter Sale Deal Claimed - SAAQi Tech");
$budgetDisplay    = !empty($budget) ? $budget : 'Winter Sale (50% OFF Promo)';
$businessDisplay  = !empty($business) ? $business : 'Not specified';
$messageDisplay   = !empty($message) ? nl2br($message) : '<em style="color:#64748b;">No client notes entered</em>';
$submissionTime   = date('D, M j, Y • g:i A T');
$leadBadge        = $leadId > 0 ? "LEAD #{$leadId}" : "NEW PROSPECT";

$emailBody = "
<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>Winter Sale Lead</title>
<style>
  body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
  table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
  img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
  @media only screen and (max-width: 620px) {
    .container-table { width: 100% !important; }
    .btn-stack { display: block !important; width: 100% !important; margin-bottom: 10px !important; }
    .cell-label { width: 100% !important; display: block !important; padding-bottom: 4px !important; }
    .cell-value { width: 100% !important; display: block !important; }
  }
</style>
</head>
<body style='margin: 0; padding: 0; background-color: #07090e; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9; line-height: 1.5;'>

<!-- Preheader text -->
<div style='display: none; font-size: 1px; color: #07090e; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;'>
  Winter Sale Deal Claimed (50% OFF) by {$name} • Type: {$website_type} • WhatsApp: {$phone}
</div>

<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #07090e; width: 100%; padding: 32px 12px;'>
  <tr>
    <td align='center'>
      
      <!-- Main Card Container -->
      <table role='presentation' class='container-table' width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width: 620px; width: 100%; background: #0f1422; border: 1px solid #1e293b; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 45px rgba(0,0,0,0.65);'>
        
        <!-- Crimson Gradient Top Accent Bar -->
        <tr>
          <td style='height: 6px; background: #c70019; background-image: linear-gradient(90deg, #c70019 0%, #e11d48 50%, #f43f5e 100%); font-size: 0; line-height: 0;'>&nbsp;</td>
        </tr>

        <!-- Header Block -->
        <tr>
          <td style='padding: 32px 32px 24px 32px; background: #0f1422; border-bottom: 1px solid #1e293b;'>
            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
              <tr>
                <td>
                  <!-- Top Promo Badges -->
                  <div style='margin-bottom: 14px;'>
                    <span style='display: inline-block; background: rgba(225, 29, 72, 0.18); border: 1px solid rgba(225, 29, 72, 0.45); color: #ff6b81; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 5px 12px; border-radius: 9999px;'>
                      ❄️ WINTER SALE • 50% OFF FLASH DEAL
                    </span>
                    <span style='display: inline-block; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; padding: 5px 10px; border-radius: 9999px; margin-left: 6px;'>
                      {$leadBadge}
                    </span>
                  </div>
                  <!-- Main Heading -->
                  <h1 style='margin: 0 0 8px 0; color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; line-height: 1.25;'>
                    Winter Sale Deal Claimed - <span style='color: #ff3355;'>50% OFF</span>
                  </h1>
                  <p style='margin: 0; color: #94a3b8; font-size: 14px; line-height: 1.5;'>
                    A new high-intent prospect has submitted an inquiry on the SAAQi Tech landing page.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Quick Action CTA Buttons -->
        <tr>
          <td style='padding: 24px 32px 18px 32px; background: #131a2c;'>
            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
              <tr>
                <td style='font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: #64748b; padding-bottom: 12px;'>
                  ⚡ Direct Client Engagement:
                </td>
              </tr>
              <tr>
                <td>
                  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
                    <tr>
                      <!-- WhatsApp CTA -->
                      <td class='btn-stack' style='padding-right: 8px; width: 50%; vertical-align: top;'>
                        <a href='{$waLink}' target='_blank' style='display: block; background: #059669; background-image: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; text-align: center; padding: 13px 16px; border-radius: 8px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);'>
                          💬 Direct WhatsApp Client
                        </a>
                      </td>
                      <!-- Email Reply CTA -->
                      <td class='btn-stack' style='padding-left: 8px; width: 50%; vertical-align: top;'>
                        <a href='{$mailtoLink}' style='display: block; background: #c70019; background-image: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; text-align: center; padding: 13px 16px; border-radius: 8px; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35);'>
                          ✉️ Reply by Email
                        </a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Lead Details Table Block -->
        <tr>
          <td style='padding: 24px 32px 32px 32px;'>
            <div style='font-size: 12px; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; color: #94a3b8; margin-bottom: 14px;'>
              📋 Lead Details & Inquiry Data:
            </div>

            <!-- Structured Dark Glass Table -->
            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border-collapse: separate; border-spacing: 0; width: 100%; border: 1px solid #1e293b; border-radius: 12px; overflow: hidden; background: #0c101b;'>
              
              <!-- Full Name -->
              <tr>
                <td class='cell-label' style='width: 35%; padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b;'>
                  Full Name
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 15px; font-weight: 700; color: #ffffff; border-bottom: 1px solid #1e293b;'>
                  {$name}
                </td>
              </tr>

              <!-- Phone / WhatsApp -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b;'>
                  Phone / WhatsApp
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 14px; font-weight: 600; border-bottom: 1px solid #1e293b;'>
                  <a href='{$waLink}' target='_blank' style='color: #34d399; text-decoration: none;'>
                    <strong>{$waDisplay}</strong> <span style='font-size: 12px; color: #10b981;'>(Open WhatsApp ↗)</span>
                  </a>
                </td>
              </tr>

              <!-- Business Email -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b;'>
                  Business Email
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 14px; font-weight: 600; border-bottom: 1px solid #1e293b;'>
                  <a href='mailto:{$email}' style='color: #38bdf8; text-decoration: none;'>
                    {$email}
                  </a>
                </td>
              </tr>

              <!-- Website Type -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b;'>
                  Website Type
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 14px; border-bottom: 1px solid #1e293b;'>
                  <span style='display: inline-block; background: #be123c; color: #ffffff; font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: 6px;'>
                    {$website_type}
                  </span>
                  <span style='display: inline-block; margin-left: 8px; color: #ff6b81; font-weight: 600; font-size: 12px;'>
                    (50% OFF Deal)
                  </span>
                </td>
              </tr>

              <!-- Business Niche -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b;'>
                  Business Niche
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 14px; color: #e2e8f0; font-weight: 500; border-bottom: 1px solid #1e293b;'>
                  {$businessDisplay}
                </td>
              </tr>

              <!-- Budget / Package -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b;'>
                  Budget / Plan
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 14px; color: #fbbf24; font-weight: 700; border-bottom: 1px solid #1e293b;'>
                  {$budgetDisplay}
                </td>
              </tr>

              <!-- Client Message -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b; vertical-align: top;'>
                  Client Message
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 13px; color: #cbd5e1; line-height: 1.6; border-bottom: 1px solid #1e293b;'>
                  <div style='background: #080c14; border-left: 3px solid #e11d48; padding: 12px 14px; border-radius: 4px; color: #e2e8f0;'>
                    {$messageDisplay}
                  </div>
                </td>
              </tr>

              <!-- Client IP -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929; border-bottom: 1px solid #1e293b;'>
                  Client IP
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 13px; font-family: \"Courier New\", Courier, monospace; color: #a5b4fc; border-bottom: 1px solid #1e293b;'>
                  {$ipAddress}
                </td>
              </tr>

              <!-- Submission Date & Time -->
              <tr>
                <td class='cell-label' style='padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; background: #131929;'>
                  Date / Time
                </td>
                <td class='cell-value' style='padding: 14px 18px; font-size: 13px; color: #94a3b8;'>
                  {$submissionTime}
                </td>
              </tr>

            </table>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style='padding: 24px 32px; background: #0c101a; border-top: 1px solid #1e293b; text-align: center;'>
            <p style='margin: 0 0 6px 0; color: #cbd5e1; font-size: 13px; font-weight: 600;'>
              SAAQi Tech Winter Sale Promotion & Lead Router
            </p>
            <p style='margin: 0; color: #64748b; font-size: 11px; line-height: 1.5;'>
              Lead safely persisted to SQLite CRM database. Delivered via Hostinger SMTP to registered admin recipients.
            </p>
          </td>
        </tr>

      </table>
      <!-- End Main Card Container -->

    </td>
  </tr>
</table>

</body>
</html>
";

// Plain text alternative body
$altBody = "WINTER SALE DEAL CLAIMED - 50% OFF\n"
         . "Lead ID: #{$leadId}\n"
         . "-----------------------------------------\n"
         . "Full Name: {$name}\n"
         . "Phone/WhatsApp: {$phone}\n"
         . "Email: {$email}\n"
         . "Website Type: {$website_type}\n"
         . "Business Niche: {$business}\n"
         . "Budget: {$budget}\n"
         . "Message: {$message}\n"
         . "Client IP: {$ipAddress}\n"
         . "Submission Time: {$submissionTime}\n\n"
         . "Direct WhatsApp: {$waLink}\n"
         . "Reply Email: mailto:{$email}\n";

// 6. & 7. Configure PHPMailer with exact Hostinger SMTP settings & dynamic routing
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@saaqitech.com';
    $mail->Password   = 'Saydev@1234';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Set lenient SSL verification for local dev environments
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ];

    // From and Reply-To
    $mail->setFrom('info@saaqitech.com', 'SAAQi Tech Winter Sale');
    $mail->addReplyTo($email, $name);

    // Dynamic recipients loop
    $addedCount = 0;
    $addedEmails = [];
    foreach ($recipientEmails as $recipient) {
        $cleanRec = trim($recipient);
        if (!empty($cleanRec) && filter_var($cleanRec, FILTER_VALIDATE_EMAIL) && !in_array($cleanRec, $addedEmails, true)) {
            $mail->addAddress($cleanRec);
            $addedEmails[] = $cleanRec;
            $addedCount++;
        }
    }
    if ($addedCount === 0) {
        $mail->addAddress('info@saaqitech.com');
        $mail->addAddress('saaqitechdeveloper@gmail.com');
        $addedEmails = ['info@saaqitech.com', 'saaqitechdeveloper@gmail.com'];
    }

    // Email content
    $mail->isHTML(true);
    $mail->Subject = '[Winter Sale 50% OFF] New Enquiry from ' . $name . ' (' . ($website_type ?: 'Website') . ')';
    $mail->Body    = $emailBody;
    $mail->AltBody = $altBody;

    // Send email
    $mail->send();
    error_log("Winter Sale notification email sent successfully to: " . implode(', ', $addedEmails));

} catch (Exception $e) {
    error_log("PHPMailer SMTP Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
}

// 9. Return success response
echo json_encode([
    'success' => true,
    'message' => 'Thank you! Your Winter Sale deal has been recorded. Our team will contact you shortly.'
]);
exit;
