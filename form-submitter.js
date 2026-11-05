document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    if(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Honeypot field check
            const honeypot = this.querySelector('input[name="website"]');
            if(honeypot && honeypot.value !== '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Spam detected!',
                    text: 'Form submission rejected.'
                });
                return;
            }
            
            // Collect form data - Winter Sale Fields
            const nameEl = this.querySelector('[name="name"]');
            const emailEl = this.querySelector('[name="email"]');
            const phoneEl = this.querySelector('[name="phone"]');
            const businessEl = this.querySelector('[name="business"]');
            const websiteTypeEl = this.querySelector('[name="website_type"]');
            const messageEl = this.querySelector('[name="message"]') || this.querySelector('[name="project"]');
            const budgetEl = this.querySelector('[name="budget"]');

            const formData = {
                name: nameEl ? nameEl.value.trim() : '',
                email: emailEl ? emailEl.value.trim() : '',
                phone: phoneEl ? phoneEl.value.trim() : '',
                business: businessEl ? businessEl.value.trim() : '',
                website_type: websiteTypeEl ? websiteTypeEl.value : '',
                message: messageEl ? messageEl.value.trim() : '',
                budget: budgetEl ? budgetEl.value : '',
                project: messageEl ? messageEl.value.trim() : ''
            };

            // If project is not explicitly given, construct a summary for backwards compatibility
            if (!formData.project) {
                formData.project = `Winter Sale Enquiry: ${formData.website_type || 'N/A'} - Niche: ${formData.business || 'N/A'}`;
            }
            
            // Validate required fields
            if (!formData.name || !formData.email || !formData.phone) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please fill in all required fields (Name, WhatsApp Number, Email)'
                });
                return;
            }
            
            // Show loading
            Swal.fire({
                title: 'Securing Your 50% OFF Deal...',
                html: 'Submitting your request to our team',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                // Send to PHP processor
                const response = await fetch('form-processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deal Claimed Successfully!',
                        text: 'Thank you! Our website specialist will contact you via WhatsApp & Email shortly.',
                        timer: 4500,
                        showConfirmButton: true,
                        confirmButtonText: 'Great, thanks!'
                    });
                    
                    // Reset form
                    form.reset();
                    // Reset custom select if present
                    const customTrigger = document.querySelector('#custom_select_website_type .winter-select-text');
                    if (customTrigger) {
                        customTrigger.textContent = 'Select Website Type';
                        customTrigger.classList.add('placeholder');
                    }
                    document.querySelectorAll('.winter-select-option.selected').forEach(el => el.classList.remove('selected'));
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Failed',
                        text: result.message || 'Something went wrong. Please try again or message us on WhatsApp.'
                    });
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Unable to reach the server. Please check your internet or message us directly on WhatsApp.'
                });
            }
        });
    }
});
