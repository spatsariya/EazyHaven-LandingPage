# EazyHaven Landing Page

A professional, responsive landing page for EazyHaven - Premium Skincare Solutions. This landing page is designed to showcase EazyHaven's skincare products and services, collect user inquiries, and grow your email subscriber list.

![EazyHaven Screenshot](https://placeholder-for-screenshot.png)

## Features

- Modern, responsive design optimized for all devices
- PHP-based modular structure for easy maintenance
- Contact form with email notifications
- Newsletter subscription functionality
- Product showcase section
- Customer testimonials
- About us section
- Comprehensive policy pages (Privacy, Terms, Returns, Shipping)

## Project Structure

```
├── index.php                # Main entry point
├── process-contact.php      # Contact form processor
├── send-mail.php            # Email functionality (from .example)
├── subscribe.php            # Newsletter subscription handler
├── css/
│   └── styles.css           # Main stylesheet
├── includes/                # Modular PHP components
│   ├── about.php            # About section
│   ├── contact.php          # Contact form
│   ├── email-config.php     # Email settings (from .example)
│   ├── features.php         # Features showcase
│   ├── footer.php           # Site footer
│   ├── header.php           # Site header
│   ├── hero.php             # Hero banner
│   ├── newsletter.php       # Newsletter signup
│   ├── products.php         # Products display
│   └── testimonials.php     # Customer testimonials
├── js/
│   └── main.js              # JavaScript functionality
└── PHPMailer/               # Email library
    ├── Exception.php
    ├── PHPMailer.php
    └── SMTP.php
```

## Installation

1. Clone this repository to your web server:
```bash
git clone https://github.com/spatsariya/EazyHaven-LandingPage.git
```

2. Set up the email configuration:
```bash
cp includes/email-config.php.example includes/email-config.php
cp send-mail.php.example send-mail.php
```

3. Edit the `includes/email-config.php` file with your actual SMTP credentials:
```php
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_USERNAME', 'your-email@example.com');
define('SMTP_PASSWORD', 'your-secure-password');
// ... other settings
```

4. Make sure your web server has PHP installed with the required extensions.

## Requirements

- PHP 7.4 or higher
- Web server (Apache, Nginx, etc.)
- SMTP server for email functionality

## Security Notes

- The repository contains example files (`.example`) with placeholder values.
- Actual configuration files with real credentials are excluded via `.gitignore`.
- Never commit sensitive information like email passwords to the repository.
- Always use the `.example` files as templates and create your own local copies.

## Customization

### Changing Content

Most content can be modified in the PHP files inside the `includes/` directory:

- Update hero text in `includes/hero.php`
- Modify products in `includes/products.php`
- Edit testimonials in `includes/testimonials.php`

### Styling Changes

Modify the CSS in `css/styles.css` to change the appearance of the landing page.

## License

[MIT License](LICENSE) - Feel free to use, modify, and distribute as needed.

## Contact

For questions or support, please contact [Shivam](mailto:s.patsariya@gmail.com).

---

© 2025 EazyHaven. All Rights Reserved.