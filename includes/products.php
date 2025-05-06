<?php
/**
 * Products section component for EazyHaven
 */

// In a real application, you might fetch products from a database
$products = [
    [
        'icon' => 'fas fa-tint',
        'name' => 'Velvet Touch Cleanser',
        'description' => 'A silky formula that caresses your skin while removing all impurities.',
        'price' => 32.99
    ],
    [
        'icon' => 'fas fa-droplet',
        'name' => 'Midnight Elixir',
        'description' => 'An intensive hydrating serum infused with rare botanical extracts.',
        'price' => 46.99
    ],
    [
        'icon' => 'fas fa-moon',
        'name' => 'Sensual Night Cream',
        'description' => 'A decadent overnight treatment that works while you dream.',
        'price' => 39.99
    ]
];
?>
<!-- Products Section -->
<section id="products" class="py-20 bg-dark">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4 text-white glow">Luxury Collection</h2>
            <p class="text-gray-400 max-w-2xl mx-auto">Discover our natural skincare solutions crafted to enhance your radiance.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($products as $product): ?>
            <!-- Product -->
            <div class="product-card rounded-lg shadow-md overflow-hidden zoom-effect">
                <div class="p-6">
                    <div class="text-brand-dark text-4xl mb-4 opacity-75"><i class="<?php echo $product['icon']; ?>"></i></div>
                    <h3 class="text-xl font-semibold mb-2 text-white"><?php echo $product['name']; ?></h3>
                    <p class="text-gray-400 mb-4"><?php echo $product['description']; ?></p>
                    <div class="flex justify-between items-center">
                        <span class="text-brand-light font-bold">$<?php echo number_format($product['price'], 2); ?></span>
                        <button class="bg-brand-dark text-white px-4 py-2 rounded-full hover:bg-brand transition duration-300 neon-border">Coming Soon</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>