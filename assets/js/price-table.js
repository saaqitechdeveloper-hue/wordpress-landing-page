const packageTableData = {
    pageTitle: "Pricing",
    pageSubtitle: "Choose the perfect plan for your business needs",

    plans: [
        { 
            name: "Basic", 
            price: "$249",
            color: "#1a237e"
        },
        { 
            name: "Professional", 
            price: "$349",
            color: "#3949ab"
        },
        { 
            name: "Elite", 
            price: "$699",
            color: "#5c6bc0"
        },
        { 
            name: "Enterprise Starting from", 
            price: "$1199",
            color: "#7986cb"
        }
    ],

    // Basic features (initially visible)
    basicFeatures: [
        { 
            name: "NO of Pages", 
            values: ["3 to 5", "5 to 8", "Unlimited", "Unlimited"]
        },
        { 
            name: "Stock Photos", 
            values: ["05", "10", "20", "Unlimited"]
        },
        { 
            name: "No of banners", 
            values: ["02", "05", "10", "Unlimited"]
        },
        { 
            name: "jQuery Slider banner", 
            values: ["01", "03", "05", "Unlimited"]
        },
        { 
            name: "Revisions", 
            values: ["Unlimited", "Unlimited", "Unlimited", "Unlimited"]
        },
        { 
            name: "Striking hover effect", 
            values: ["✓", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Design", 
            values: [
                "Template Based", 
                "Design", 
                "Custom Design", 
                "Bespoke design, Dynamic, Interactive, and High-end Design"
            ]
        }
    ],

    // Hidden features (initially hidden)
    hiddenFeatures: [
        { 
            name: "Online Payment Integration", 
            values: ["✗", "✗", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Mobile responsive", 
            values: ["✗", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "CMS", 
            values: ["✗", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Custom forms", 
            values: ["✗", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Search Bar/Filters", 
            values: ["✓", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Google Friendly Sitemap", 
            values: ["✓", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Core Features", 
            values: ["✗", "✗", "Booking / Online Store etc", "Booking / Online Store etc"]
        }
    ],

    // Value added services (ONLY 3 items as requested)
    valueAddedServices: [
        { 
            name: "Dedicated account manager", 
            values: ["✓", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Social Media Page Design", 
            values: ["✓", "✓", "✓", "✓"],
            isCheckmark: true
        },
        { 
            name: "Complete deployment", 
            values: ["✓", "✓", "✓", "✓"],
            isCheckmark: true
        }
    ],

    // Button text
    toggleButtonText: {
        show: "View Complete Package Details",
        hide: "Hide Complete Package Details"
    },

    // Section titles
    sectionTitles: {
        advancedFeatures: "Advanced Features",
        valueAddedServices: "Value Added Services"
    }
};

// ========== FUNCTION TO GENERATE DESKTOP TABLE ==========
function generateDesktopTable() {
    const table = document.getElementById('package_table-desktop');
    const totalColumns = packageTableData.plans.length + 1; // +1 for feature column

    // Create table header
    let tableHTML = `
        <thead>
            <tr>
                <th>Features</th>
    `;

    // Add plan headers
    packageTableData.plans.forEach(plan => {
        tableHTML += `<th>${plan.name}</th>`;
    });

    tableHTML += `</tr></thead><tbody>`;

    // Add pricing row
    tableHTML += `<tr><td>Pricing $</td>`;
    packageTableData.plans.forEach(plan => {
        tableHTML += `<td>${plan.price}</td>`;
    });
    tableHTML += `</tr>`;

    // Add basic features (initially visible)
    packageTableData.basicFeatures.forEach(feature => {
        tableHTML += `<tr><td>${feature.name}</td>`;
        feature.values.forEach(value => {
            if (feature.isCheckmark) {
                const className = value === "✓" ? "package_table-checkmark" : "package_table-crossmark";
                tableHTML += `<td><span class="${className}">${value}</span></td>`;
            } else {
                tableHTML += `<td>${value}</td>`;
            }
        });
        tableHTML += `</tr>`;
    });

    // Add Advanced Features heading row - SINGLE TD with colspan
    tableHTML += `
        <tr class="package_table-hidden-row">
            <td colspan="${totalColumns}" class="package_table-section-heading">
                ${packageTableData.sectionTitles.advancedFeatures}
            </td>
        </tr>
    `;

    // Add hidden features
    packageTableData.hiddenFeatures.forEach(feature => {
        tableHTML += `<tr class="package_table-hidden-row"><td>${feature.name}</td>`;
        feature.values.forEach(value => {
            if (feature.isCheckmark) {
                const className = value === "✓" ? "package_table-checkmark" : "package_table-crossmark";
                tableHTML += `<td><span class="${className}">${value}</span></td>`;
            } else {
                tableHTML += `<td>${value}</td>`;
            }
        });
        tableHTML += `</tr>`;
    });

    // Add Value Added Services heading row - SINGLE TD with colspan
    tableHTML += `
        <tr class="package_table-hidden-row">
            <td colspan="${totalColumns}" class="package_table-section-heading">
                ${packageTableData.sectionTitles.valueAddedServices}
            </td>
        </tr>
    `;

    // Add value added services
    packageTableData.valueAddedServices.forEach(service => {
        tableHTML += `<tr class="package_table-hidden-row"><td>${service.name}</td>`;
        service.values.forEach(value => {
            tableHTML += `<td><span class="package_table-checkmark">${value}</span></td>`;
        });
        tableHTML += `</tr>`;
    });

    // Add select plan buttons row (initially hidden)
    tableHTML += `<tr id="package_table-desktop-buttons-row" style="display: none;"><td></td>`;
    packageTableData.plans.forEach((plan, index) => {
        tableHTML += `<td><button class="package_table-select-btn-desktop" data-plan-index="${index}">Select Plan</button></td>`;
    });
    tableHTML += `</tr>`;

    tableHTML += `</tbody>`;
    table.innerHTML = tableHTML;
    }

// ========== FUNCTION TO GENERATE MOBILE CARDS ==========
function generateMobileCards() {
    const container = document.getElementById('package_table-mobile-cards');
    container.innerHTML = '';

    // Create a card for each plan
    packageTableData.plans.forEach((plan, planIndex) => {
        const card = document.createElement('div');
        card.className = 'package_table-card';
        card.id = `package_table-card-${planIndex}`;
        
        let cardHTML = `
            <div class="package_table-card-header" style="background-color: ${plan.color}">
                <h3>${plan.name}</h3>
                <div class="package_table-card-price">${plan.price}</div>
            </div>
            <div class="package_table-card-features">
        `;
        
        // Add pricing as first feature
        cardHTML += `
            <div class="package_table-feature-item">
                <span class="package_table-feature-name">Pricing $</span>
                <span class="package_table-feature-value">${plan.price}</span>
            </div>
        `;
        
        // Add basic features
        packageTableData.basicFeatures.forEach(feature => {
            const value = feature.values[planIndex];
            cardHTML += `
                <div class="package_table-feature-item">
                    <span class="package_table-feature-name">${feature.name}</span>
                    <span class="package_table-feature-value ${feature.isCheckmark ? (value === "✓" ? "package_table-checkmark" : "package_table-crossmark") : ""}">
                        ${value}
                    </span>
                </div>
            `;
        });
        
        // Add hidden features section (initially collapsed)
        cardHTML += `
            <div class="package_table-mobile-hidden" id="package_table-hidden-${planIndex}">
                <div class="package_table-section-title">${packageTableData.sectionTitles.advancedFeatures}</div>
        `;
        
        packageTableData.hiddenFeatures.forEach(feature => {
            const value = feature.values[planIndex];
            cardHTML += `
                <div class="package_table-feature-item">
                    <span class="package_table-feature-name">${feature.name}</span>
                    <span class="package_table-feature-value ${feature.isCheckmark ? (value === "✓" ? "package_table-checkmark" : "package_table-crossmark") : ""}">
                        ${value}
                    </span>
                </div>
            `;
        });
        
        // Add value added services (ONLY 3 items)
        cardHTML += `
            <div class="package_table-value-added">
                <h4>${packageTableData.sectionTitles.valueAddedServices}</h4>
        `;
        
        packageTableData.valueAddedServices.forEach(service => {
            cardHTML += `
                <div class="package_table-value-added-item">
                    <i class="fas fa-check-circle"></i>
                    <span>${service.name}</span>
                </div>
            `;
        });
        
        cardHTML += `
            </div>
            </div>
            
            <!-- Card Toggle Button (for mobile only) -->
            <button class="package_table-card-toggle" id="package_table-card-toggle-${planIndex}" data-card-index="${planIndex}">
                <span>View Complete Package Details</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            
            <!-- Select Plan Button (initially hidden) -->
            <button class="package_table-select-btn" id="package_table-select-${planIndex}" data-plan-index="${planIndex}">Select Plan</button>
        `;
        
        card.innerHTML = cardHTML;
        container.appendChild(card);
    });
}

// ========== INITIALIZE PAGE ==========
document.addEventListener('DOMContentLoaded', function() {
// Set page title and subtitle (if elements exist)
const titleElement = document.querySelector('.package_table-title');
const subtitleElement = document.querySelector('.package_table-subtitle');

if (titleElement) {
    titleElement.textContent = packageTableData.pageTitle;
}

if (subtitleElement) {
    subtitleElement.textContent = packageTableData.pageSubtitle;
}

// Generate both views
generateDesktopTable();
generateMobileCards();

// Desktop toggle button functionality
const desktopToggleBtn = document.getElementById('package_table-toggle-btn');
const desktopToggleText = desktopToggleBtn.querySelector('span');
const desktopHiddenRows = document.querySelectorAll('.package_table-hidden-row');
const desktopButtonsRow = document.getElementById('package_table-desktop-buttons-row');
const desktopSelectButtons = document.querySelectorAll('.package_table-select-btn-desktop');

let desktopExpanded = false;

desktopToggleBtn.addEventListener('click', function() {
    if (!desktopExpanded) {
        // Show hidden rows in desktop table
        desktopHiddenRows.forEach((row, index) => {
            setTimeout(() => {
                row.classList.add('visible');
            }, index * 30);
        });
        
        // Show select plan buttons row
        desktopButtonsRow.style.display = 'table-row';
        desktopSelectButtons.forEach(btn => {
            btn.classList.add('visible');
        });
        
        desktopToggleText.textContent = packageTableData.toggleButtonText.hide;
        desktopToggleBtn.classList.add('expanded');
        desktopExpanded = true;
    } else {
        // Hide rows in desktop table
        desktopHiddenRows.forEach((row, index) => {
            setTimeout(() => {
                row.classList.remove('visible');
            }, index * 20);
        });
        
        // Hide select plan buttons row
        desktopButtonsRow.style.display = 'none';
        desktopSelectButtons.forEach(btn => {
            btn.classList.remove('visible');
        });
        
        desktopToggleText.textContent = packageTableData.toggleButtonText.show;
        desktopToggleBtn.classList.remove('expanded');
        desktopExpanded = false;
    }
});

// Mobile card toggle buttons functionality
document.addEventListener('click', function(e) {
    // Handle mobile card toggle buttons
    if (e.target.classList.contains('package_table-card-toggle') || 
        e.target.parentElement.classList.contains('package_table-card-toggle')) {
        
        const toggleBtn = e.target.classList.contains('package_table-card-toggle') ? 
                            e.target : e.target.parentElement;
        const cardIndex = toggleBtn.getAttribute('data-card-index');
        const toggleText = toggleBtn.querySelector('span');
        const hiddenSection = document.getElementById(`package_table-hidden-${cardIndex}`);
        const selectBtn = document.getElementById(`package_table-select-${cardIndex}`);
        
        if (hiddenSection.classList.contains('expanded')) {
            // Collapse
            hiddenSection.classList.remove('expanded');
            selectBtn.classList.remove('visible');
            toggleText.textContent = packageTableData.toggleButtonText.show;
            toggleBtn.classList.remove('expanded');
        } else {
            // Expand
            hiddenSection.classList.add('expanded');
            selectBtn.classList.add('visible');
            toggleText.textContent = packageTableData.toggleButtonText.hide;
            toggleBtn.classList.add('expanded');
        }
    }
    
    // Handle desktop select plan buttons
    if (e.target.classList.contains('package_table-select-btn-desktop')) {
        const buttonIndex = e.target.getAttribute('data-plan-index');
        const planName = packageTableData.plans[buttonIndex].name;
        alert(`You've selected the ${planName} plan!`);
        
        // Visual feedback
        e.target.style.backgroundColor = '#2e7d32';
        setTimeout(() => {
            e.target.style.backgroundColor = '#4caf50';
        }, 300);
    }
    
    // Handle mobile select plan buttons
    if (e.target.classList.contains('package_table-select-btn')) {
        const planIndex = e.target.getAttribute('data-plan-index');
        const planName = packageTableData.plans[planIndex].name;
        alert(`You've selected the ${planName} plan!`);
        
        // Visual feedback
        e.target.style.backgroundColor = '#2e7d32';
        setTimeout(() => {
            e.target.style.backgroundColor = '#4caf50';
        }, 300);
    }
});
});