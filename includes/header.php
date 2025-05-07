<?php
/**
 * Header component for EazyHaven website
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'EazyHaven - Premium Skincare Solutions'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <!-- hCaptcha Script -->
    <script src="https://hcaptcha.com/1/api.js" async defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            light: '#f0e6d2',
                            DEFAULT: '#e6d7c3',
                            dark: '#c9b18f',
                        },
                        dark: {
                            light: '#2c2822',
                            DEFAULT: '#1e1c19',
                            dark: '#0f0e0c',
                        }
                    },
                    fontFamily: {
                        sans: ['Montserrat', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-dark text-gray-200 font-sans">
    <!-- Navigation -->
    <nav class="bg-dark-dark bg-opacity-80 backdrop-blur-md shadow-md fixed w-full z-50 border-b border-brand-dark border-opacity-30">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-leaf text-brand text-2xl"></i>
                    <h1 class="text-2xl font-bold text-white glow">EazyHaven</h1>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="#home" class="font-medium text-gray-300 hover:text-brand hover:glow transition duration-300">Home</a>
                    <a href="#products" class="font-medium text-gray-300 hover:text-brand hover:glow transition duration-300">Products</a>
                    <a href="#about" class="font-medium text-gray-300 hover:text-brand hover:glow transition duration-300">About</a>
                    <a href="#testimonials" class="font-medium text-gray-300 hover:text-brand hover:glow transition duration-300">Testimonials</a>
                    <a href="#contact" class="font-medium text-gray-300 hover:text-brand hover:glow transition duration-300">Contact</a>
                </div>
                <div class="md:hidden">
                    <button id="menu-toggle" class="text-gray-300 hover:text-brand">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
            <!-- Mobile menu -->
            <div id="mobile-menu" class="md:hidden hidden mt-3 pb-2">
                <a href="#home" class="block py-2 text-gray-300 hover:text-brand transition duration-300">Home</a>
                <a href="#products" class="block py-2 text-gray-300 hover:text-brand transition duration-300">Products</a>
                <a href="#about" class="block py-2 text-gray-300 hover:text-brand transition duration-300">About</a>
                <a href="#testimonials" class="block py-2 text-gray-300 hover:text-brand transition duration-300">Testimonials</a>
                <a href="#contact" class="block py-2 text-gray-300 hover:text-brand transition duration-300">Contact</a>
            </div>
        </div>
    </nav>

    <main>