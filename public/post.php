<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Veritabanı bağlantısı
$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

// Post ID'yi al
$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    http_response_code(404);
    echo "Post not found.";
    exit;
}

// Postu veritabanından çek
$stmt = $pdo->prepare('SELECT posts.*, users.username AS author FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ? AND posts.status = "published"');
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    echo "Post not found.";
    exit;
}

// Yorum ekleme işlemi
$comment_error = '';
if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $comment_content = trim($_POST['comment_content']);
    if (strlen($comment_content) < 3) {
        $comment_error = 'Yorum en az 3 karakter olmalı.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content, is_approved, created_at) VALUES (?, ?, ?, 1, NOW())');
        $stmt->execute([$post_id, $_SESSION['user_id'], $comment_content]);
        header('Location: post.php?id=' . $post_id); // Yorumdan sonra sayfayı yenile
        exit;
    }
}

// Yorumları çek
$stmt = $pdo->prepare('SELECT c.*, u.username FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.is_approved = 1 ORDER BY c.created_at DESC');
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?> - Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Blog</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="posts.php">Posts</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
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
    <div class="container my-5">
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <p class="text-muted">
            By <?php echo htmlspecialchars($post['author']); ?>
            <?php if (!empty($post['published_at'])): ?>
                on <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
            <?php endif; ?>
        </p>
        <hr>
        <div>
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>
        <hr>
        <h3>Yorumlar</h3>
        <?php if (count($comments) === 0): ?>
            <div class="alert alert-info">Henüz yorum yok.</div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="mb-3 p-3 border rounded">
                    <strong><?php echo htmlspecialchars($comment['username'] ?? 'Anonim'); ?></strong><br>
                    <span><?php echo nl2br(htmlspecialchars($comment['content'])); ?></span>
                    <div class="text-muted small mt-1"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="comment_content" class="form-label">Yorumunuz</label>
                    <textarea class="form-control" id="comment_content" name="comment_content" rows="3" required></textarea>
                </div>
                <?php if ($comment_error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($comment_error); ?></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Yorum Gönder</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning mt-4">Yorum yapmak için <a href="login.php">giriş yapmalısınız</a>.</div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 