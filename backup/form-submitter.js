document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    if(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Honeypot field check
            const honeypot = this.querySelector('input[name="website"]');
            if(honeypot.value !== '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Spam detected!',
                    text: 'Form submission rejected.'
                });
                return;
            }
            
            // Collect form data
            const formData = {
                name: this.querySelector('input[name="name"]').value,
                email: this.querySelector('input[name="email"]').value,
                phone: this.querySelector('input[name="phone"]').value,
                project: this.querySelector('input[name="project"]').value
            };
            
            // Show loading
            Swal.fire({
                title: 'Submitting...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                // Send to your PHP processor
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
                        title: 'Success!',
                        text: 'Form submitted successfully',
                        timer: 3000
                    });
                    form.reset(); // Clear form
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Submission failed'
                    });
                }
            } catch(error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Please check your internet connection'
                });
            }
        });
    }
});