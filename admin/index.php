<?php
/**
 * SAAQi Tech - Admin Portal Dashboard
 * Obsidian Luxe Dark Glass Interface (Antigravity Dashboard UI)
 * 
 * Secure Lead Management & Email Routing Engine
 */

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/db.php';

// Enforce strict authenticated session
requireAdminAuth();

// Generate cryptographic CSRF token for asynchronous state modifications
$csrfToken = generateCsrfToken();

// Fetch SQLite connection & current notification settings
$pdo = getDatabaseConnection();
$currentNotificationEmails = '';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'notification_emails'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && isset($row['setting_value'])) {
        $currentNotificationEmails = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Failed to load settings in dashboard view: " . $e->getMessage());
}

$adminUsername = $_SESSION['admin_username'] ?? 'admin';
$adminRole = ucfirst($_SESSION['admin_role'] ?? 'Superadmin');
$avatarLetter = strtoupper(substr($adminUsername, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAAQi Tech - Admin Portal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Design Tokens & Obsidian Luxe Styles -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(getAdminUrl('assets/css/admin-tokens.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(getAdminUrl('assets/css/admin-dashboard.css'), ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars(getAssetUrl('assets/img/fave-icon.png'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <!-- Ambient Background Glows -->
    <div class="admin-ambient-glow ambient-top-right"></div>
    <div class="admin-ambient-glow ambient-bottom-left"></div>

    <div class="admin-shell">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="<?php echo htmlspecialchars(getAssetUrl('assets/img/ft-logo.avif'), ENT_QUOTES, 'UTF-8'); ?>" alt="SAAQi Tech Logo" class="sidebar-logo">
            </div>

            <nav class="sidebar-nav">
                <a href="#inquiries" class="nav-link active" data-tab="inquiries">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span>Winter Inquiries</span>
                    <span class="nav-badge-count" id="sidebar-new-count" style="display: none;">0</span>
                </a>
                <a href="#settings" class="nav-link" data-tab="settings">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>Email Routing Settings</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="admin-profile">
                    <div class="admin-avatar"><?php echo htmlspecialchars($avatarLetter, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="admin-info">
                        <span class="admin-name"><?php echo htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="admin-role"><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
                <a href="<?php echo htmlspecialchars(getAdminUrl('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-logout" title="Sign Out">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <button type="button" class="mobile-nav-toggle" aria-label="Toggle navigation menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="page-title-box">
                        <h1 id="page-title">Winter Sale Inquiries</h1>
                        <p id="page-subtitle">Real-time prospective client leads from the landing page</p>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="pulse-indicator">
                        <span class="pulse-dot"></span>
                        <span>Live SQLite Connected</span>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content Container -->
            <div class="admin-content">
                <!-- Section 1: Inquiries -->
                <div id="section-inquiries" style="display: flex; flex-direction: column; gap: 28px;">
                    <!-- 4 Stat Cards Grid -->
                    <div class="stats-grid">
                        <!-- Card 1: Total Inquiries -->
                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-label">Total Inquiries</span>
                                <div class="stat-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-total">0</div>
                            <div class="stat-meta">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>All-time landing page leads</span>
                            </div>
                        </div>

                        <!-- Card 2: Today's Leads -->
                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-label">Today's Leads</span>
                                <div class="stat-icon" style="color: var(--accent-cyan); background: rgba(56, 189, 248, 0.08);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-today">0</div>
                            <div class="stat-meta">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                    <polyline points="17 6 23 6 23 12"></polyline>
                                </svg>
                                <span>Submissions past 24 hrs</span>
                            </div>
                        </div>

                        <!-- Card 3: Unread / New -->
                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-label">Unread / New</span>
                                <div class="stat-icon" style="color: var(--status-new-text); background: var(--status-new-bg);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-new" style="color: var(--status-new-text);">0</div>
                            <div class="stat-meta">
                                <span class="pulse-dot" style="width: 6px; height: 6px; background: var(--status-new-text); box-shadow: 0 0 6px var(--status-new-text);"></span>
                                <span>Action required</span>
                            </div>
                        </div>

                        <!-- Card 4: Converted -->
                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-label">Converted</span>
                                <div class="stat-icon" style="color: var(--status-converted-text); background: var(--status-converted-bg);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-value" id="stat-converted" style="color: var(--status-converted-text);">0</div>
                            <div class="stat-meta">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Won clients</span>
                            </div>
                        </div>
                    </div>

                    <!-- Table Panel -->
                    <div class="table-panel">
                        <!-- Toolbar -->
                        <div class="panel-toolbar">
                            <div class="toolbar-left">
                                <div class="search-box">
                                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    <input type="text" id="search-inquiries" class="search-input" placeholder="Search by name, email, phone..." autocomplete="off">
                                </div>
                                <select id="filter-status" class="filter-select" aria-label="Filter by status">
                                    <option value="">All Statuses</option>
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="converted">Converted</option>
                                    <option value="archived">Archived</option>
                                </select>
                                <select id="filter-service" class="filter-select" aria-label="Filter by service">
                                    <option value="">All Services</option>
                                    <option value="WordPress">WordPress</option>
                                    <option value="Custom WordPress">Custom WordPress</option>
                                    <option value="WordPress + AI">WordPress + AI</option>
                                </select>
                            </div>
                            <div class="toolbar-right">
                                <button type="button" id="btn-refresh" class="btn-action btn-secondary" title="Refresh Inquiries">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="23 4 23 10 17 10"></polyline>
                                        <polyline points="1 20 1 14 7 14"></polyline>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                    </svg>
                                    <span>Refresh</span>
                                </button>
                                <button type="button" id="btn-export-csv" class="btn-action btn-secondary" title="Export Inquiries to CSV">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    <span>Export CSV</span>
                                </button>
                            </div>
                        </div>

                        <!-- Responsive Table Container -->
                        <div class="table-responsive">
                            <table class="inquiries-table">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">ID</th>
                                        <th>Client</th>
                                        <th>WhatsApp</th>
                                        <th>Email</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="inquiries-tbody">
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                </svg>
                                                <p>Loading inquiries...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Email Routing Settings -->
                <div id="section-settings" class="settings-panel">
                    <div class="settings-card">
                        <div>
                            <h2 class="settings-title">Email Inquiry Routing</h2>
                            <p class="settings-subtitle">Specify which email addresses should receive instant lead alerts when a user submits the Winter Sale form.</p>
                        </div>

                        <form id="settings-form">
                            <div class="form-group">
                                <label for="setting-notification-emails" class="form-label">Notification Recipients (Multiple Allowed)</label>
                                <input type="text" id="setting-notification-emails" class="form-input" value="<?php echo htmlspecialchars($currentNotificationEmails, ENT_QUOTES, 'UTF-8'); ?>" placeholder="info@saaqitech.com, sales@company.com">
                                <span class="form-help">Separate multiple email addresses with a comma (e.g. info@saaqitech.com, sales@company.com, alerts@domain.com).</span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px; flex-wrap: wrap;">
                                <button type="submit" class="btn-action btn-primary">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                        <polyline points="7 3 7 8 15 8"></polyline>
                                    </svg>
                                    <span>Save Settings</span>
                                </button>
                                <button type="button" id="btn-test-smtp" class="btn-action btn-secondary">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"></line>
                                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                    </svg>
                                    <span>Send Test Email</span>
                                </button>
                            </div>

                            <div id="test-smtp-status" style="display: none; padding: 12px 16px; border-radius: var(--radius-md); background: var(--bg-input); border: 1px solid var(--border-hairline); font-size: 13px; margin-top: 14px;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Slide-Over Detail Drawer -->
    <div id="drawer-backdrop" class="drawer-backdrop"></div>
    <div id="lead-drawer" class="detail-drawer">
        <div class="drawer-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h3>Inquiry Details</h3>
                <span class="service-tag" id="drawer-lead-id">#--</span>
            </div>
            <button type="button" id="btn-close-drawer" class="btn-close-drawer" aria-label="Close details drawer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="drawer-body">
            <div class="detail-field">
                <span class="field-label">Full Name</span>
                <div class="field-value" id="drawer-name">-</div>
            </div>

            <div class="detail-field">
                <span class="field-label">Email Address</span>
                <div class="field-value">
                    <a href="#" id="drawer-email" class="email-link">-</a>
                </div>
            </div>

            <div class="detail-field">
                <span class="field-label">WhatsApp Contact</span>
                <div class="field-value" style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <span id="drawer-phone">-</span>
                    <a href="#" target="_blank" id="drawer-btn-wa" class="btn-action btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px; display: none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                        <span>WhatsApp Chat</span>
                    </a>
                </div>
            </div>

            <div class="detail-field">
                <span class="field-label">Service Type</span>
                <div class="field-value">
                    <span class="service-tag" id="drawer-service">WordPress</span>
                </div>
            </div>

            <div class="detail-field">
                <span class="field-label">Business / Niche</span>
                <div class="field-value" id="drawer-business">-</div>
            </div>

            <div class="detail-field">
                <span class="field-label">Client Message</span>
                <div class="field-value field-message" id="drawer-message">-</div>
            </div>

            <div class="detail-field">
                <span class="field-label">Client IP Address</span>
                <div class="field-value" id="drawer-ip" style="font-family: var(--font-mono); font-size: 12px;">-</div>
            </div>

            <div class="detail-field">
                <span class="field-label">Submission Date</span>
                <div class="field-value timestamp-text" id="drawer-date">-</div>
            </div>

            <div class="detail-field">
                <span class="field-label">Quick Status Update</span>
                <select id="drawer-status-select" class="filter-select" style="width: 100%;">
                    <option value="new">New (Unread)</option>
                    <option value="contacted">Contacted</option>
                    <option value="converted">Converted</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="detail-field">
                <span class="field-label">Internal Staff Notes</span>
                <textarea id="drawer-notes" class="form-input" style="height: 90px; padding: 10px 14px; resize: vertical; font-family: var(--font-body);" placeholder="Add internal follow-up notes here..."></textarea>
            </div>
        </div>

        <div class="drawer-footer">
            <button type="button" id="drawer-btn-save" class="btn-action btn-primary" style="flex: 1; justify-content: center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Save Changes</span>
            </button>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="admin-toast-container" class="admin-toast-container"></div>

    <!-- Dynamic Application Configuration -->
    <script>
    window.ADMIN_CONFIG = {
        base_url: '<?php echo htmlspecialchars(getAppBaseUrl(), ENT_QUOTES, "UTF-8"); ?>',
        admin_url: '<?php echo htmlspecialchars(getAdminUrl(), ENT_QUOTES, "UTF-8"); ?>',
        api_url: '<?php echo htmlspecialchars(getAdminUrl("api.php"), ENT_QUOTES, "UTF-8"); ?>',
        csrf_token: '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8"); ?>'
    };
    </script>

    <!-- GSAP 3.12 CDN & Admin Dashboard Controller -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="<?php echo htmlspecialchars(getAdminUrl('assets/js/admin-dashboard.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
