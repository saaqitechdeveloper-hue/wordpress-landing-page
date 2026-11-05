<?php
/**
 * SAAQi Tech - Admin Login Portal
 * Obsidian Luxe Dark Glass Interface (Antigravity Dashboard UI)
 * Protected with WAF, Rate Limiting, CSRF, and Secure Session Fingerprinting
 */

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/db.php';

initSecureSession();

// If already logged in, redirect to dashboard
if (isAdminAuthenticated()) {
    header('Location: ' . getAdminUrl('index.php'));
    exit;
}

$pdo = getDatabaseConnection();
$clientIp = getClientIp();
$rateCheck = checkRateLimit($pdo, $clientIp);
$errorMessage = '';

if ($rateCheck['locked']) {
    $errorMessage = $rateCheck['message'];
}

// Handle POST login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($rateCheck['locked']) {
        $errorMessage = $rateCheck['message'];
    } else {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrfToken)) {
            $errorMessage = 'Security token expired. Please refresh and try again.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $errorMessage = 'Please enter both username and password.';
            } else {
                // Query admin user
                $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Successful login
                    logLoginAttempt($pdo, $clientIp, true);
                    session_regenerate_id(true);

                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user_id']   = $user['id'];
                    $_SESSION['admin_username']  = $user['username'];
                    $_SESSION['admin_role']      = $user['role'];
                    $_SESSION['auth_fingerprint']= hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . getClientIpSubnet());

                    // Update last login
                    $upd = $pdo->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $upd->execute([$user['id']]);

                    header('Location: ' . getAdminUrl('index.php'));
                    exit;
                } else {
                    // Failed login
                    logLoginAttempt($pdo, $clientIp, false);
                    $rateCheck = checkRateLimit($pdo, $clientIp);
                    if ($rateCheck['locked']) {
                        $errorMessage = $rateCheck['message'];
                    } else {
                        $errorMessage = 'Invalid username or password. Please try again.';
                    }
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login - SAAQi Tech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(getAdminUrl('assets/css/admin-tokens.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars(getAssetUrl('assets/img/fave-icon.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            z-index: 2;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-surface-1);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border-hairline);
            border-radius: var(--radius-xl);
            padding: 36px 32px;
            box-shadow: var(--shadow-lg), 0 0 40px rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            gap: 24px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-primary), transparent);
        }

        .login-header {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .login-logo {
            height: 44px;
            width: auto;
            object-fit: contain;
        }

        .login-title {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: 0.5px;
        }

        .login-sub {
            font-size: 13px;
            color: var(--text-muted);
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .form-input {
            height: 46px;
            background: var(--bg-input);
            border: 1px solid var(--border-hairline);
            border-radius: var(--radius-md);
            padding: 0 16px;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px var(--accent-subtle);
        }

        .btn-submit {
            height: 48px;
            background: linear-gradient(135deg, var(--accent-primary), #ff3d5c);
            color: #ffffff;
            font-family: var(--font-heading);
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(255, 26, 64, 0.4);
            transition: all 0.2s ease;
            margin-top: 6px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255, 26, 64, 0.55);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert-error {
            background: rgba(255, 30, 60, 0.12);
            border: 1px solid rgba(255, 30, 60, 0.35);
            color: #ff4d6c;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-footer {
            text-align: center;
            font-size: 11.5px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-hairline);
            padding-top: 16px;
        }
    </style>
</head>
<body>
    <div class="admin-ambient-glow ambient-top-right"></div>
    <div class="admin-ambient-glow ambient-bottom-left"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <img src="<?php echo htmlspecialchars(getAssetUrl('assets/img/ft-logo.avif'), ENT_QUOTES, 'UTF-8'); ?>" alt="SAAQi Tech Logo" class="login-logo">
                <h1 class="login-title">Admin Dashboard</h1>
                <p class="login-sub">Enter credentials to manage client inquiries</p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars(getAdminUrl('login.php'), ENT_QUOTES, 'UTF-8'); ?>" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="admin" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-submit" <?php echo $rateCheck['locked'] ? 'disabled' : ''; ?>>
                    Sign In to Dashboard &rarr;
                </button>
            </form>

            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> SAAQi Tech. Hardened Security &bull; 256-Bit SSL Protected
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof gsap !== 'undefined') {
                gsap.from('.login-card', {
                    y: 30,
                    opacity: 0,
                    scale: 0.96,
                    duration: 0.55,
                    ease: 'power3.out'
                });
            }
        });
    </script>
</body>
</html>
