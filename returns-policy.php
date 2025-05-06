<?php
/**
 * EazyHaven - Returns Policy Page
 */

// Set page title
$pageTitle = 'Returns Policy - EazyHaven';

// Include header
include_once 'includes/header.php';
?>

<!-- Returns Policy Content -->
<section class="py-20 pt-32 bg-dark">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="glass-card rounded-lg shadow-lg p-8 neon-border">
                <h1 class="text-3xl font-bold mb-6 text-white glow text-center">Returns Policy</h1>
                
                <div class="text-gray-300 space-y-6">
                    <p class="mb-4">
                        Last updated: <?php echo date('F d, Y'); ?>
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Our Returns Philosophy</h2>
                    <p>
                        At EazyHaven, we believe in the quality of our natural skincare products and want you to be completely satisfied with your purchase. We understand that sometimes a product might not be right for your skin type or needs, which is why we've created this hassle-free returns policy.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Return Eligibility</h2>
                    <p>
                        To be eligible for a return, your item must be unused and in the same condition that you received it. It must also be in the original packaging with all seals intact. Several types of goods are exempt from being returned, including:
                    </p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Products that have been opened or used</li>
                        <li>Gift cards</li>
                        <li>Downloadable products</li>
                        <li>Sample products</li>
                        <li>Custom or personalized items</li>
                    </ul>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Return Window</h2>
                    <p>
                        You have 30 days from the date of delivery to initiate a return. After 30 days, we cannot offer you a refund or exchange.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Return Process</h2>
                    <p>
                        To start a return, please follow these steps:
                    </p>
                    <ol class="list-decimal pl-6 space-y-2">
                        <li>Contact our customer service team at <a href="mailto:returns@eazyhaven.com" class="text-brand-dark hover:text-brand transition duration-300">returns@eazyhaven.com</a> or call (123) 456-7890 to obtain a Return Merchandise Authorization (RMA) number.</li>
                        <li>Include your order number and reason for the return in your communication.</li>
                        <li>Once you receive your RMA number, package your product securely with all original packaging and include the RMA number on the outside of the package.</li>
                        <li>Ship your return to the address provided by our customer service team.</li>
                    </ol>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Refunds</h2>
                    <p>
                        Once your return is received and inspected, we will send you an email to notify you that we have received your returned item. We will also notify you of the approval or rejection of your refund.
                    </p>
                    <p>
                        If approved, your refund will be processed, and a credit will automatically be applied to your original method of payment within 5-7 business days. Please note that depending on your credit card company, it may take an additional 2-10 business days for the refund to appear on your statement.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Return Shipping</h2>
                    <p>
                        You will be responsible for paying the return shipping costs. The original shipping costs are non-refundable. If you receive a refund, the cost of return shipping will be deducted from your refund.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Exchanges</h2>
                    <p>
                        We are happy to exchange products if you've received damaged or defective items, or if you'd like to try a different product. For exchanges, please follow the same process as returns. Once we receive the original product, we will ship out your exchange item.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Damaged or Defective Items</h2>
                    <p>
                        If you receive a damaged or defective product, please contact us immediately at <a href="mailto:returns@eazyhaven.com" class="text-brand-dark hover:text-brand transition duration-300">returns@eazyhaven.com</a> with photos of the damaged item. We will work with you to replace the item or issue a refund. In these cases, we will cover the cost of return shipping.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Sale Items</h2>
                    <p>
                        Only regularly priced items may be refunded; sale items cannot be refunded and are final sale unless received damaged or defective.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Contact Us</h2>
                    <p>
                        If you have any questions about our returns policy, please contact our customer service team at:
                    </p>
                    <p class="mt-2">
                        Email: <a href="mailto:returns@eazyhaven.com" class="text-brand-dark hover:text-brand transition duration-300">returns@eazyhaven.com</a><br>
                        Phone: (123) 456-7890
                    </p>
                </div>

                <div class="mt-8 text-center">
                    <a href="index.php" class="bg-brand-dark text-white px-6 py-2 rounded-full font-semibold hover:bg-brand transition duration-300 neon-border inline-block">
                        Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';
?>