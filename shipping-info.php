<?php
/**
 * EazyHaven - Shipping Information Page
 */

// Set page title
$pageTitle = 'Shipping Information - EazyHaven';

// Include header
include_once 'includes/header.php';
?>

<!-- Shipping Info Content -->
<section class="py-20 pt-32 bg-dark">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="glass-card rounded-lg shadow-lg p-8 neon-border">
                <h1 class="text-3xl font-bold mb-6 text-white glow text-center">Shipping Information</h1>
                
                <div class="text-gray-300 space-y-6">
                    <p class="mb-4">
                        Last updated: <?php echo date('F d, Y'); ?>
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Our Shipping Policy</h2>
                    <p>
                        At EazyHaven, we strive to deliver your natural skincare products as quickly and efficiently as possible. We want your shopping experience to be seamless from browse to doorstep. This page provides detailed information about our shipping policies, methods, and estimated delivery times.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Processing Times</h2>
                    <p>
                        All orders are processed within 1-2 business days (excluding weekends and holidays) after receiving your order confirmation email. You will receive another notification when your order has shipped.
                    </p>
                    <p>
                        If we are experiencing a high volume of orders, shipments may be delayed by a few days. Please allow additional days in transit for delivery. If there will be a significant delay in the shipment of your order, we will contact you via email.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Shipping Methods & Delivery Times</h2>
                    <table class="w-full text-gray-300 border-collapse">
                        <thead>
                            <tr class="border-b border-brand-dark">
                                <th class="py-2 text-left">Shipping Method</th>
                                <th class="py-2 text-left">Estimated Delivery Time</th>
                                <th class="py-2 text-left">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-brand-dark border-opacity-30">
                                <td class="py-3">Standard Shipping</td>
                                <td class="py-3">5-7 business days</td>
                                <td class="py-3">
                                    Free for orders over $50<br>
                                    $5.99 for orders under $50
                                </td>
                            </tr>
                            <tr class="border-b border-brand-dark border-opacity-30">
                                <td class="py-3">Expedited Shipping</td>
                                <td class="py-3">2-3 business days</td>
                                <td class="py-3">$12.99</td>
                            </tr>
                            <tr>
                                <td class="py-3">Next Day Delivery</td>
                                <td class="py-3">1 business day</td>
                                <td class="py-3">$24.99</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="text-sm italic">
                        *Business days are Monday through Friday, excluding federal holidays.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Shipping Destinations</h2>
                    <p>
                        Currently, we ship to all 50 U.S. states and select international destinations. International shipping rates are calculated at checkout based on destination and package weight.
                    </p>
                    <p>
                        Please note that international orders may be subject to import duties and taxes which are levied once the package reaches the destination country. These charges are the responsibility of the recipient and are not included in our shipping charges.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Tracking Your Order</h2>
                    <p>
                        Once your order has shipped, you will receive a shipping confirmation email with a tracking number. You can use this tracking number to check the status of your delivery. Please allow 24 hours for the tracking information to become available.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Shipping Restrictions</h2>
                    <p>
                        Due to shipping regulations, certain products may have restrictions on where they can be shipped. If this applies to any items in your order, we will notify you before processing.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Missing or Damaged Items</h2>
                    <p>
                        If your package appears damaged upon arrival, please contact our customer service team immediately. We recommend taking photos of the damaged package before opening it.
                    </p>
                    <p>
                        For missing items in your shipment, please contact us within 48 hours of receiving your package. We will work with you to resolve the issue promptly.
                    </p>

                    <h2 class="text-xl font-semibold text-brand-light mb-3">Contact Our Shipping Department</h2>
                    <p>
                        If you have any questions about our shipping policy or a specific order, please contact our dedicated shipping team at:
                    </p>
                    <p class="mt-2">
                        Email: <a href="mailto:shipping@eazyhaven.com" class="text-brand-dark hover:text-brand transition duration-300">shipping@eazyhaven.com</a><br>
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