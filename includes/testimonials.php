<?php
/**
 * Testimonials section component for EazyHaven
 */

// In a real application, you might fetch testimonials from a database
$testimonials = [
    [
        'image' => 'https://randomuser.me/api/portraits/women/45.jpg',
        'name' => 'Sarah Johnson',
        'rating' => 5,
        'text' => '"Using EazyHaven\'s products feels like giving my skin a sensual retreat every day. The textures are divine, the scents are intoxicating, and the results are absolutely transformative."'
    ],
    [
        'image' => 'https://randomuser.me/api/portraits/men/32.jpg',
        'name' => 'Michael Thomson',
        'rating' => 4.5,
        'text' => '"As someone who values quality and sophistication, EazyHaven delivers on all fronts. Their products have transformed my skincare routine into a daily luxury ritual."'
    ],
    [
        'image' => 'https://randomuser.me/api/portraits/women/68.jpg',
        'name' => 'Emily Rodriguez',
        'rating' => 5,
        'text' => '"The subtle, sensual experience of using EazyHaven\'s products brings a moment of luxurious self-care to my day. My skin has never looked or felt better."'
    ]
];
?>
<!-- Testimonials Section -->
<section id="testimonials" class="py-20 bg-dark">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4 text-white glow">Devotees' Experiences</h2>
            <p class="text-gray-400 max-w-2xl mx-auto">Discover the transformative experiences of our devoted customers.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($testimonials as $testimonial): ?>
            <!-- Testimonial -->
            <div class="glass-card p-6 rounded-lg shadow-md zoom-effect">
                <div class="flex items-center mb-4">
                    <img src="<?php echo $testimonial['image']; ?>" alt="Testimonial" class="w-12 h-12 rounded-full mr-4 border border-brand">
                    <div>
                        <h4 class="font-semibold text-white"><?php echo $testimonial['name']; ?></h4>
                        <div class="text-brand flex">
                            <?php 
                            $fullStars = floor($testimonial['rating']);
                            $hasHalfStar = $testimonial['rating'] - $fullStars >= 0.5;
                            
                            // Output full stars
                            for ($i = 0; $i < $fullStars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            
                            // Output half star if needed
                            if ($hasHalfStar) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <p class="text-gray-400 italic"><?php echo $testimonial['text']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>