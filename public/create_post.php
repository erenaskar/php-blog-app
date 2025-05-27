<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'author'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, slug, content, excerpt, status, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        $result = $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $slug,
            $content,
            $excerpt,
            $status,
            $published_at
        ]);
        if ($result) {
            header('Location: posts.php');
            exit;
        } else {
            $error = 'Failed to create post.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
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
                    <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'author'])): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="create_post.php">Add Post</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
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
    <div class="container my-5" style="max-width:700px;">
        <h2 class="mb-4">Create New Post</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="excerpt" class="form-label">Excerpt</label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content" rows="6" required></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Create Post</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 