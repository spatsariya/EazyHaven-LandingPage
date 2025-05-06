<?php
/**
 * EazyHaven - Premium Skincare Solutions
 * Main index file that assembles all components
 */

// Set page title
$pageTitle = 'EazyHaven - Premium Skincare Solutions';

// Include database connection or other configuration files here
// require_once 'config/database.php';

// Include header
include_once 'includes/header.php';

// Include page components
include_once 'includes/hero.php';
include_once 'includes/features.php';
include_once 'includes/products.php';
include_once 'includes/about.php';
include_once 'includes/testimonials.php';
include_once 'includes/newsletter.php';
include_once 'includes/contact.php';

// Include footer
include_once 'includes/footer.php';
?>