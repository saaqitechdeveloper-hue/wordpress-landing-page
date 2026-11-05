/**
 * SAAQi Tech - Antigravity Admin Dashboard Controller
 * - GSAP Coordinated Motion Timelines
 * - Real-Time Search, Filtering & Pagination
 * - Slide-Over Detail Drawer
 * - Dynamic Status Updates & Staff Notes
 * - Dynamic Email Routing Settings Management & SMTP Test
 */

document.addEventListener('DOMContentLoaded', () => {
    initGsapEntrance();
    initNavigationTabs();
    initInquiriesTable();
    initSettingsForm();
    initMobileNav();
});

// 1. GSAP Entrance Animations
function initGsapEntrance() {
    if (typeof gsap === 'undefined') return;

    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    tl.from('.admin-topbar', {
        y: -20,
        opacity: 0,
        duration: 0.45
    })
    .from('.stat-card', {
        y: 25,
        opacity: 0,
        duration: 0.5,
        stagger: 0.08
    }, '-=0.2')
    .from('.table-panel', {
        y: 20,
        opacity: 0,
        duration: 0.45
    }, '-=0.25');
}

// 2. Navigation Tab Switcher (Inquiries vs Settings)
function initNavigationTabs() {
    const navLinks = document.querySelectorAll('.nav-link[data-tab]');
    const inquiriesSection = document.getElementById('section-inquiries');
    const settingsSection = document.getElementById('section-settings');
    const pageTitle = document.getElementById('page-title');
    const pageSubtitle = document.getElementById('page-subtitle');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            const tab = link.getAttribute('data-tab');
            if (tab === 'settings') {
                inquiriesSection.style.display = 'none';
                settingsSection.classList.add('active');
                pageTitle.textContent = 'Email Routing Settings';
                pageSubtitle.textContent = 'Configure recipient email addresses and test SMTP delivery';
                
                if (typeof gsap !== 'undefined') {
                    gsap.from('.settings-card', { opacity: 0, y: 15, duration: 0.35, stagger: 0.1 });
                }
            } else {
                settingsSection.classList.remove('active');
                inquiriesSection.style.display = 'flex';
                pageTitle.textContent = 'Winter Sale Inquiries';
                pageSubtitle.textContent = 'Real-time prospective client leads from the landing page';
                
                if (typeof gsap !== 'undefined') {
                    gsap.from('.table-panel', { opacity: 0, y: 10, duration: 0.3 });
                }
            }

            // Close mobile nav if open
            document.querySelector('.admin-sidebar').classList.remove('mobile-open');
        });
    });
}

// 3. Inquiries Table & Live Filter Controller
function initInquiriesTable() {
    const tableBody = document.getElementById('inquiries-tbody');
    const searchInput = document.getElementById('search-inquiries');
    const statusFilter = document.getElementById('filter-status');
    const serviceFilter = document.getElementById('filter-service');
    const btnRefresh = document.getElementById('btn-refresh');

    let allInquiries = [];

    async function loadInquiries() {
        try {
            const res = await fetch(`${window.ADMIN_CONFIG.api_url}?action=list_inquiries`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                allInquiries = data.inquiries;
                updateStats(data.stats);
                renderTable();
            } else {
                showToast(data.message || 'Failed to fetch inquiries', 'error');
            }
        } catch (err) {
            console.error('Fetch error:', err);
            showToast('Network error while loading leads', 'error');
        }
    }

    function updateStats(stats) {
        if (!stats) return;
        document.getElementById('stat-total').textContent = stats.total ?? 0;
        document.getElementById('stat-today').textContent = stats.today ?? 0;
        document.getElementById('stat-new').textContent = stats.new ?? 0;
        document.getElementById('stat-converted').textContent = stats.converted ?? 0;
        
        const badgeCount = document.getElementById('sidebar-new-count');
        if (badgeCount) {
            badgeCount.textContent = stats.new ?? 0;
            badgeCount.style.display = (stats.new > 0) ? 'inline-block' : 'none';
        }
    }

    function renderTable() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedStatus = statusFilter.value;
        const selectedService = serviceFilter.value;

        const filtered = allInquiries.filter(item => {
            const matchesQuery = !query || 
                (item.name && item.name.toLowerCase().includes(query)) ||
                (item.email && item.email.toLowerCase().includes(query)) ||
                (item.phone && item.phone.toLowerCase().includes(query)) ||
                (item.business && item.business.toLowerCase().includes(query));

            const matchesStatus = !selectedStatus || item.status === selectedStatus;
            const matchesService = !selectedService || item.website_type === selectedService;

            return matchesQuery && matchesStatus && matchesService;
        });

        if (filtered.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <p>No matching inquiries found</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = filtered.map(lead => {
            const rawPhone = lead.phone || '';
            const cleanPhone = rawPhone.replace(/[^0-9]/g, '');
            const waUrl = cleanPhone ? `https://wa.me/${cleanPhone}` : '#';
            const statusClass = lead.status || 'new';

            return `
                <tr class="table-row ${statusClass === 'new' ? 'unread' : ''}" data-id="${lead.id}">
                    <td><span class="service-tag">#${lead.id}</span></td>
                    <td>
                        <div class="client-name-cell">
                            <span class="client-name">${escapeHtml(lead.name)}</span>
                            <span class="client-business">${escapeHtml(lead.business || 'N/A')}</span>
                        </div>
                    </td>
                    <td>
                        ${cleanPhone ? `
                            <a href="${waUrl}" target="_blank" class="phone-link" title="Open WhatsApp Chat">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                <span>${escapeHtml(rawPhone)}</span>
                            </a>
                        ` : '<span style="color:var(--text-muted);">-</span>'}
                    </td>
                    <td>
                        <a href="mailto:${escapeHtml(lead.email)}" class="email-link">
                            ${escapeHtml(lead.email)}
                        </a>
                    </td>
                    <td>
                        <span class="service-tag">${escapeHtml(lead.website_type || 'WordPress')}</span>
                    </td>
                    <td>
                        <span class="badge-status ${statusClass}">
                            <span class="badge-dot"></span>
                            ${escapeHtml(statusClass)}
                        </span>
                    </td>
                    <td>
                        <span class="timestamp-text">${formatDate(lead.created_at)}</span>
                    </td>
                </tr>
            `;
        }).join('');

        // Attach click listeners to open drawer
        tableBody.querySelectorAll('.table-row').forEach(row => {
            row.addEventListener('click', (e) => {
                // If clicked direct link (WhatsApp or Email), let browser open it
                if (e.target.closest('a')) return;
                const id = row.getAttribute('data-id');
                const lead = allInquiries.find(item => item.id == id);
                if (lead) openDetailDrawer(lead);
            });
        });
    }

    searchInput.addEventListener('input', debounce(renderTable, 200));
    statusFilter.addEventListener('change', renderTable);
    serviceFilter.addEventListener('change', renderTable);
    if (btnRefresh) btnRefresh.addEventListener('click', loadInquiries);

    // Initial load
    loadInquiries();

    // Export CSV
    const btnExport = document.getElementById('btn-export-csv');
    if (btnExport) {
        btnExport.addEventListener('click', () => {
            window.location.href = `${window.ADMIN_CONFIG.api_url}?action=export_csv&csrf_token=${window.ADMIN_CONFIG.csrf_token}`;
        });
    }
}

// 4. Detail Drawer Controller
function openDetailDrawer(lead) {
    const drawer = document.getElementById('lead-drawer');
    const backdrop = document.getElementById('drawer-backdrop');

    document.getElementById('drawer-lead-id').textContent = `#${lead.id}`;
    document.getElementById('drawer-name').textContent = lead.name || '';
    document.getElementById('drawer-email').textContent = lead.email || '';
    document.getElementById('drawer-email').href = `mailto:${lead.email}`;
    
    const phoneEl = document.getElementById('drawer-phone');
    phoneEl.textContent = lead.phone || 'N/A';
    const cleanPhone = (lead.phone || '').replace(/[^0-9]/g, '');
    const btnWa = document.getElementById('drawer-btn-wa');
    if (cleanPhone) {
        btnWa.href = `https://wa.me/${cleanPhone}?text=Hi%20${encodeURIComponent(lead.name)}%2C%20thank%20you%20for%20reaching%20out%20to%20SAAQi%20Tech!`;
        btnWa.style.display = 'inline-flex';
    } else {
        btnWa.style.display = 'none';
    }

    document.getElementById('drawer-service').textContent = lead.website_type || 'WordPress';
    document.getElementById('drawer-business').textContent = lead.business || 'N/A';
    document.getElementById('drawer-message').textContent = lead.message || 'No additional message provided';
    document.getElementById('drawer-ip').textContent = lead.ip_address || 'Unknown';
    document.getElementById('drawer-date').textContent = formatDate(lead.created_at);

    // Status select
    const statusSelect = document.getElementById('drawer-status-select');
    statusSelect.value = lead.status || 'new';

    // Staff notes
    const notesInput = document.getElementById('drawer-notes');
    notesInput.value = lead.notes || '';

    // Save status button
    const btnSaveStatus = document.getElementById('drawer-btn-save');
    btnSaveStatus.onclick = async () => {
        const newStatus = statusSelect.value;
        const newNotes = notesInput.value;
        try {
            const res = await fetch(`${window.ADMIN_CONFIG.api_url}?action=update_lead`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    id: lead.id,
                    status: newStatus,
                    notes: newNotes,
                    csrf_token: window.ADMIN_CONFIG.csrf_token
                })
            });
            const data = await res.json();
            if (data.success) {
                lead.status = newStatus;
                lead.notes = newNotes;
                showToast('Inquiry status updated successfully', 'success');
                closeDetailDrawer();
                // Re-render table
                const searchInput = document.getElementById('search-inquiries');
                searchInput.dispatchEvent(new Event('input'));
            } else {
                showToast(data.message || 'Update failed', 'error');
            }
        } catch (err) {
            showToast('Error saving updates', 'error');
        }
    };

    // Open animations
    backdrop.classList.add('active');
    drawer.classList.add('active');
    
    if (typeof gsap !== 'undefined') {
        gsap.fromTo(drawer, { x: 480 }, { x: 0, duration: 0.35, ease: 'power3.out' });
    }
}

function closeDetailDrawer() {
    const drawer = document.getElementById('lead-drawer');
    const backdrop = document.getElementById('drawer-backdrop');

    if (typeof gsap !== 'undefined') {
        gsap.to(drawer, {
            x: 480,
            duration: 0.25,
            ease: 'power3.in',
            onComplete: () => {
                drawer.classList.remove('active');
                backdrop.classList.remove('active');
            }
        });
    } else {
        drawer.classList.remove('active');
        backdrop.classList.remove('active');
    }
}

document.getElementById('btn-close-drawer')?.addEventListener('click', closeDetailDrawer);
document.getElementById('drawer-backdrop')?.addEventListener('click', closeDetailDrawer);

// 5. Settings Form & Multiple Notification Emails Management
function initSettingsForm() {
    const settingsForm = document.getElementById('settings-form');
    const btnTestSmtp = document.getElementById('btn-test-smtp');
    const testStatusBox = document.getElementById('test-smtp-status');

    if (settingsForm) {
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const emailsInput = document.getElementById('setting-notification-emails').value;

            try {
                const res = await fetch(`${window.ADMIN_CONFIG.api_url}?action=save_settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({
                        notification_emails: emailsInput,
                        csrf_token: window.ADMIN_CONFIG.csrf_token
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Notification email settings saved successfully!', 'success');
                } else {
                    showToast(data.message || 'Failed to save settings', 'error');
                }
            } catch (err) {
                showToast('Failed to connect to server', 'error');
            }
        });
    }

    if (btnTestSmtp) {
        btnTestSmtp.addEventListener('click', async () => {
            testStatusBox.style.display = 'block';
            testStatusBox.innerHTML = '<span style="color:var(--text-muted)">Sending test email through Hostinger SMTP (Port 465)...</span>';
            btnTestSmtp.disabled = true;

            try {
                const res = await fetch(`${window.ADMIN_CONFIG.api_url}?action=test_smtp`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ csrf_token: window.ADMIN_CONFIG.csrf_token })
                });
                const data = await res.json();
                if (data.success) {
                    testStatusBox.innerHTML = `<span style="color:var(--accent-emerald); font-weight:600;">Success: Test email delivered! Checked inbox: ${escapeHtml(data.recipients.join(', '))}</span>`;
                    showToast('SMTP Test Passed!', 'success');
                } else {
                    testStatusBox.innerHTML = `<span style="color:var(--accent-primary); font-weight:600;">Error: ${escapeHtml(data.message)}</span>`;
                    showToast('SMTP Test Failed', 'error');
                }
            } catch (err) {
                testStatusBox.innerHTML = '<span style="color:var(--accent-primary);">Network or Server Error during SMTP test</span>';
            } finally {
                btnTestSmtp.disabled = false;
            }
        });
    }
}

// 6. Mobile Sidebar Toggle
function initMobileNav() {
    const toggle = document.querySelector('.mobile-nav-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });
    }
}

// 7. Toast Notification Utility
function showToast(message, type = 'success') {
    const container = document.getElementById('admin-toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `admin-toast ${type}`;
    
    const iconSvg = type === 'success' 
        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>'
        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff1f3d" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

    toast.innerHTML = `${iconSvg}<span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        if (typeof gsap !== 'undefined') {
            gsap.to(toast, {
                opacity: 0,
                y: -10,
                duration: 0.25,
                onComplete: () => toast.remove()
            });
        } else {
            toast.remove();
        }
    }, 4000);
}

// Helpers
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
