<?php
/**
 * Newsletter section component for EazyHaven
 */
?>
<!-- Newsletter Section -->
<section class="py-16 bg-brand-dark text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-4 glow">Join Our Inner Circle</h2>
        <p class="max-w-2xl mx-auto mb-8 text-gray-200">Be the first to receive exclusive offers, sensual skincare tips, and early access to new product launches.</p>
        <form id="newsletterForm" class="max-w-md mx-auto">
            <div class="flex flex-col sm:flex-row gap-4">
                <input type="email" id="newsletterEmail" placeholder="Your email address" class="flex-grow px-4 py-3 rounded-full text-dark-dark focus:outline-none bg-white bg-opacity-90 focus:bg-opacity-100 transition duration-300" required>
                <button type="submit" class="bg-dark text-white border border-brand px-6 py-3 rounded-full font-semibold hover:bg-dark-light transition duration-300 neon-border">Subscribe</button>
            </div>
            <div id="newsletterMessage" class="form-message rounded-lg text-center mt-2"></div>
        </form>
    </div>
</section>