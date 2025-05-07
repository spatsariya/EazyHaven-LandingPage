document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    menuToggle.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });

    // Contact form handling with custom AJAX submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form elements
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            // Fix: Get hCaptcha response properly
            const hcaptchaResponse = hcaptcha.getResponse();
            
            // Form validation
            if (!name || !email || !message) {
                showContactMessage('Please fill in all required fields.', 'error');
                return;
            }
            
            if (!validateEmail(email)) {
                showContactMessage('Please enter a valid email address.', 'error');
                return;
            }
            
            // hCaptcha validation
            if (!hcaptchaResponse) {
                showContactMessage('Please complete the CAPTCHA verification.', 'error');
                return;
            }
            
            // Disable the submit button and show loading state
            const submitButton = document.getElementById('submitButton');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('subject', subject);
            formData.append('message', message);
            formData.append('h-captcha-response', hcaptchaResponse);
            
            // Use fetch API to submit form
            fetch('process-contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showContactMessage(data.message, 'success');
                    contactForm.reset();
                    
                    // Reset hCaptcha
                    if (typeof hcaptcha !== 'undefined') {
                        hcaptcha.reset();
                    }
                    
                    // Redirect to thank you page after a brief delay
                    setTimeout(() => {
                        window.location.href = 'thank-you.html';
                    }, 2000);
                } else {
                    showContactMessage(data.message || 'There was an error sending your message.', 'error');
                    
                    // Reset hCaptcha
                    if (typeof hcaptcha !== 'undefined') {
                        hcaptcha.reset();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showContactMessage('There was an error sending your message. Please try again later.', 'error');
                
                // Reset hCaptcha
                if (typeof hcaptcha !== 'undefined') {
                    hcaptcha.reset();
                }
            })
            .finally(() => {
                // Re-enable the submit button
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }
    
    // Contact form message display function
    function showContactMessage(message, type) {
        const formStatus = document.getElementById('formStatus');
        const successMsg = document.getElementById('successMsg');
        const errorMsg = document.getElementById('errorMsg');
        
        // Hide both messages first
        successMsg.classList.add('hidden');
        errorMsg.classList.add('hidden');
        
        // Show the appropriate message
        if (type === 'success') {
            successMsg.textContent = message;
            successMsg.classList.remove('hidden');
        } else {
            errorMsg.textContent = message;
            errorMsg.classList.remove('hidden');
        }
        
        // Show the container
        formStatus.classList.remove('hidden');
        
        // Hide the message after 5 seconds if it's an error
        if (type === 'error') {
            setTimeout(() => {
                formStatus.classList.add('hidden');
            }, 5000);
        }
    }

    // Newsletter form handling with AJAX
    const newsletterForm = document.getElementById('newsletterForm');
    const newsletterMessage = document.getElementById('newsletterMessage');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('newsletterEmail').value;
            
            // Email validation
            if (!email || !validateEmail(email)) {
                showMessage(newsletterMessage, 'Please enter a valid email address.', 'error');
                return;
            }
            
            // AJAX request to handle the newsletter subscription
            const formData = new FormData();
            formData.append('email', email);
            formData.append('timestamp', new Date().toISOString());
            
            // Use fetch API to submit form
            fetch('subscribe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage(newsletterMessage, data.message || 'Thank you for subscribing to our newsletter!', 'success');
                    newsletterForm.reset();
                } else {
                    showMessage(newsletterMessage, data.message || 'There was an error. Please try again later.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(newsletterMessage, 'There was an error. Please try again later.', 'error');
            });
        });
    }
    
    // Email validation function
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
    
    // Message display function
    function showMessage(element, message, type) {
        element.textContent = message;
        element.classList.add('show');
        
        if (type === 'success') {
            element.className = 'form-message show bg-green-800 text-white rounded-lg';
        } else {
            element.className = 'form-message show bg-red-800 text-white rounded-lg';
        }
        
        // Hide message after 5 seconds
        setTimeout(() => {
            element.classList.remove('show');
        }, 5000);
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Close mobile menu if open
            if (!mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
            }
            
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});