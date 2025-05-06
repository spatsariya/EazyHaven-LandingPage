<?php
/**
 * Contact section component for EazyHaven
 */
?>
<!-- Contact Form Section -->
<section id="contact" class="py-20 bg-dark">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4 text-white glow">Connect With Us</h2>
            <p class="text-gray-400 max-w-2xl mx-auto">We'd love to hear your thoughts, answer your questions, or just connect.</p>
        </div>
        <div class="max-w-3xl mx-auto">
            <div class="glass-card rounded-lg shadow-lg p-8 neon-border">
                <form id="contactForm" action="https://formsubmit.co/contact@eazyhaven.com" method="POST">
                    <!-- Honeypot to prevent spam -->
                    <input type="text" name="_honey" style="display:none">
                    <!-- Disable captcha -->
                    <input type="hidden" name="_captcha" value="false">
                    <!-- Success page (can be set to your own success page) -->
                    <input type="hidden" name="_next" value="#contact">
                    <!-- Subject for the email -->
                    <input type="hidden" name="_subject" value="New contact form submission from EazyHaven website">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-300" for="name">Full Name</label>
                            <input class="w-full bg-dark-light border border-brand-dark border-opacity-50 p-3 rounded-lg focus:outline-none focus:border-brand text-white" 
                                   type="text" id="name" name="name" placeholder="Your name" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-300" for="email">Email Address</label>
                            <input class="w-full bg-dark-light border border-brand-dark border-opacity-50 p-3 rounded-lg focus:outline-none focus:border-brand text-white" 
                                   type="email" id="email" name="email" placeholder="Your email" required>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-2 text-gray-300" for="subject">Subject</label>
                        <input class="w-full bg-dark-light border border-brand-dark border-opacity-50 p-3 rounded-lg focus:outline-none focus:border-brand text-white" 
                               type="text" id="subject" name="subject" placeholder="Subject of your message">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-2 text-gray-300" for="message">Message</label>
                        <textarea class="w-full bg-dark-light border border-brand-dark border-opacity-50 p-3 rounded-lg focus:outline-none focus:border-brand text-white" 
                                  id="message" name="message" rows="4" placeholder="Your message" required></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="bg-brand text-white px-8 py-3 rounded-full font-semibold hover:bg-brand-light transition duration-300 neon-border">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>