<?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <!-- Hero Section -->
            <section class="hero-section">
                <h1 class="hero-title">Welcome to Student Portal Blog</h1>
                <p class="hero-subtitle">
                    Discover insights, tips, and stories from our student community. 
                    Learn, grow, and connect with fellow students on your academic journey.
                </p>
                <div class="hero-cta">
                    <a href="blog_list.php" class="cta-button">Explore Articles</a>
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'write_post.php' : 'register.php'; ?>" class="cta-button secondary">
                        <?php echo isset($_SESSION['user_id']) ? 'Write a Post' : 'Join Community'; ?>
                    </a>
                </div>
            </section>

            <!-- Features Section -->
            <section class="features-section">
                <h2 class="section-title">Why Choose Our Platform?</h2>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">📚</div>
                        <h3>Quality Content</h3>
                        <p>Access curated articles and resources written by students, for students. Get insights that matter to your academic success.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🤝</div>
                        <h3>Community Driven</h3>
                        <p>Connect with peers, share experiences, and build lasting relationships within our vibrant student community.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🚀</div>
                        <h3>Career Growth</h3>
                        <p>Get tips on internships, job applications, and career development from students who've been there.</p>
                    </div>
                </div>
            </section>

            <!-- Latest Blog Posts -->
            <!-- <section id="latest-posts">
                <h2 class="section-title">Latest Articles</h2>
                <div class="blog-grid">
                    <?php
                    // Sample blog posts (replace with database query)
                    $sample_posts = [
                        [
                            'title' => 'How to Ace Your Final Exams',
                            'excerpt' => 'Discover proven strategies and study techniques that will help you perform your best during exam season.',
                            'author' => 'Sarah Johnson',
                            'date' => '2025-06-08',
                            'link' => 'post.php?id=1'
                        ],
                        [
                            'title' => 'Top 10 Study Apps for Students',
                            'excerpt' => 'Explore the best mobile applications that can revolutionize your study routine and boost productivity.',
                            'author' => 'Mike Chen',
                            'date' => '2025-06-07',
                            'link' => 'post.php?id=2'
                        ],
                        [
                            'title' => 'Balancing Work and Study Life',
                            'excerpt' => 'Learn practical tips for managing part-time work while maintaining academic excellence.',
                            'author' => 'Emma Davis',
                            'date' => '2025-06-06',
                            'link' => 'post.php?id=3'
                        ],
                        [
                            'title' => 'Building Your Professional Network',
                            'excerpt' => 'Start building valuable connections early in your academic career with these networking strategies.',
                            'author' => 'Alex Rivera',
                            'date' => '2025-06-05',
                            'link' => 'post.php?id=4'
                        ],
                        [
                            'title' => 'Mental Health Tips for Students',
                            'excerpt' => 'Prioritize your wellbeing with these essential mental health practices for academic success.',
                            'author' => 'Dr. Lisa Wong',
                            'date' => '2025-06-04',
                            'link' => 'post.php?id=5'
                        ],
                        [
                            'title' => 'Getting the Most Out of Online Learning',
                            'excerpt' => 'Maximize your online education experience with these practical tips and tools.',
                            'author' => 'Tom Anderson',
                            'date' => '2025-06-03',
                            'link' => 'post.php?id=6'
                        ]
                    ];

                    foreach ($sample_posts as $post): ?>
                        <article class="blog-card">
                            <div class="blog-meta">
                                <span>By <?php echo htmlspecialchars($post['author']); ?></span>
                                <span><?php echo date('M j, Y', strtotime($post['date'])); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <a href="<?php echo htmlspecialchars($post['link']); ?>" class="read-more">Read More</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section> -->
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>