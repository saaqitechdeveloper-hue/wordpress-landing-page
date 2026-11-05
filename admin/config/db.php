<?php
/**
 * SAAQi Tech - SQLite Database Manager & Auto-Migrator
 * - PDO Prepared-Statements only (100% SQL Injection Immune)
 * - Auto-schema creation & migrations
 * - Dynamic notification email routing settings
 */

require_once __DIR__ . '/security.php';

function getDatabaseConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbDir = dirname(__DIR__) . '/data';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    $dbPath = $dbDir . '/inquiries.sqlite';

    try {
        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Run migrations
        runDatabaseMigrations($pdo);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new RuntimeException("Database error occurred. Please try again.");
    }
}

function runDatabaseMigrations(PDO $pdo) {
    // 1. Admin users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT DEFAULT 'superadmin',
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Inquiries table
    $pdo->exec("CREATE TABLE IF NOT EXISTS inquiries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        website_type TEXT,
        business TEXT,
        message TEXT,
        budget TEXT,
        ip_address TEXT,
        user_agent TEXT,
        status TEXT DEFAULT 'new',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Settings table (Key/Value for multiple recipient emails & SMTP)
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Rate-limiting & Brute Force Tracker
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        ip_address TEXT PRIMARY KEY,
        attempts INTEGER DEFAULT 1,
        last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
        locked_until DATETIME
    )");

    // 5. Seed default admin user: admin / Saydev@123
    $checkUser = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $checkUser->execute(['admin']);
    if (!$checkUser->fetch()) {
        $hash = password_hash('Saydev@123', PASSWORD_BCRYPT, ['cost' => 12]);
        $seed = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (?, ?, ?)");
        $seed->execute(['admin', $hash, 'superadmin']);
    }

    // 6. Seed default settings
    $defaultSettings = [
        'notification_emails' => 'info@saaqitech.com, saaqitechdeveloper@gmail.com',
        'smtp_host'           => 'smtp.hostinger.com',
        'smtp_port'           => '465',
        'smtp_user'           => 'info@saaqitech.com',
        'smtp_pass'           => 'Saydev@1234',
        'smtp_secure'         => 'ssl',
        'sender_name'         => 'SAAQi Tech Winter Sale'
    ];

    $checkSetting = $pdo->prepare("SELECT setting_key FROM settings WHERE setting_key = ?");
    $insertSetting = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");

    foreach ($defaultSettings as $k => $v) {
        $checkSetting->execute([$k]);
        if (!$checkSetting->fetch()) {
            $insertSetting->execute([$k, $v]);
        }
    }
}

// Helper: Get configured notification emails as an array
function getNotificationEmails(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'notification_emails'");
    $stmt->execute();
    $row = $stmt->fetch();
    
    if ($row && !empty($row['setting_value'])) {
        $raw = explode(',', $row['setting_value']);
        $emails = [];
        foreach ($raw as $e) {
            $cleaned = trim($e);
            if (filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $cleaned;
            }
        }
        if (!empty($emails)) {
            return $emails;
        }
    }
    return ['info@saaqitech.com', 'saaqitechdeveloper@gmail.com'];
}

// Helper: Check rate limiting
function checkRateLimit(PDO $pdo, $ip) {
    $stmt = $pdo->prepare("SELECT attempts, locked_until FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $rec = $stmt->fetch();

    if ($rec) {
        if (!empty($rec['locked_until'])) {
            $lockTime = strtotime($rec['locked_until']);
            if (time() < $lockTime) {
                $remain = ceil(($lockTime - time()) / 60);
                return [
                    'locked' => true,
                    'minutes' => $remain,
                    'message' => "Too many failed attempts. Try again in {$remain} minute(s)."
                ];
            }
        }
    }
    return ['locked' => false];
}

// Helper: Record login outcome
function logLoginAttempt(PDO $pdo, $ip, $success) {
    if ($success) {
        $del = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $del->execute([$ip]);
    } else {
        $stmt = $pdo->prepare("SELECT attempts FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $rec = $stmt->fetch();

        if ($rec) {
            $newAttempts = $rec['attempts'] + 1;
            $lockedUntil = null;
            if ($newAttempts >= 5) {
                // Lockout for 15 minutes
                $lockedUntil = date('Y-m-d H:i:s', time() + 900);
            }
            $upd = $pdo->prepare("UPDATE login_attempts SET attempts = ?, last_attempt = CURRENT_TIMESTAMP, locked_until = ? WHERE ip_address = ?");
            $upd->execute([$newAttempts, $lockedUntil, $ip]);
        } else {
            $ins = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, CURRENT_TIMESTAMP)");
            $ins->execute([$ip]);
        }
    }
}
