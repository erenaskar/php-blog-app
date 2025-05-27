<?php

// Define root path
define('ROOT_PATH', dirname(__DIR__));
define('BASE_PATH', dirname(__DIR__));

// Load composer autoloader
require_once ROOT_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');

// Start session
session_start();

// Veritabanı bağlantısı
$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

// Blog yazılarını çek
$stmt = $pdo->query('SELECT posts.*, users.username AS author FROM posts JOIN users ON posts.user_id = users.id WHERE status = "published" ORDER BY published_at DESC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .post-card {
            transition: transform 0.2s;
        }
        .post-card:hover {
            transform: translateY(-5px);
        }
        .post-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Blog</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="posts.php">Posts</a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'author'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="create_post.php">Add Post</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Featured Post -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card bg-dark text-white">
                    <div class="card-body p-5">
                        <h1 class="card-title">Welcome to Our Blog</h1>
                        <p class="card-text">This is a simple blog platform built with PHP and MySQL.</p>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="register.php" class="btn btn-primary">Get Started</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Grid -->
        <div class="row">
            <?php if (empty($posts)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No posts available yet. Check back soon!
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 post-card">
                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                <div class="post-meta mb-2">
                                    By <?php echo htmlspecialchars($post['author']); ?> on <?php if (!empty($post['published_at'])): ?>
                                        <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary">Read More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Blog</h5>
                    <p>A simple blog platform built with PHP and MySQL.</p>
                </div>
                <div class="col-md-3">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="posts.php" class="text-white">Posts</a></li>
                        <li><a href="login.php" class="text-white">Login</a></li>
                        <li><a href="register.php" class="text-white">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li>Email: contact@example.com</li>
                        <li>Phone: (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Blog. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?> 