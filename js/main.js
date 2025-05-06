document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    menuToggle.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });

    // Contact form handling
    const contactForm = document.getElementById('contactForm');
    contactForm.addEventListener('submit', function(e) {
        // Form submission is handled by FormSubmit.co service
        // No need to prevent default here as we're using a real form submission
    });

    // Newsletter form handling with AJAX
    const newsletterForm = document.getElementById('newsletterForm');
    const newsletterMessage = document.getElementById('newsletterMessage');
    
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
        .then(response => {
            // For demo purposes, simulate success
            // In production, you would check the actual response
            showMessage(newsletterMessage, 'Thank you for subscribing to our newsletter!', 'success');
            newsletterForm.reset();
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage(newsletterMessage, 'There was an error. Please try again later.', 'error');
        });
    });
    
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