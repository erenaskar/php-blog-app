<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

// Yorum silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $delete_id = (int)$_POST['delete_comment_id'];
    $stmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
    $stmt->execute([$delete_id]);
    header('Location: comments.php');
    exit;
}

$stmt = $pdo->query('SELECT c.*, u.username, p.title as post_title FROM comments c LEFT JOIN users u ON c.user_id = u.id LEFT JOIN posts p ON c.post_id = p.id ORDER BY c.created_at DESC');
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comments - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .content {
            padding: 20px;
        }
        .nav-link {
            color: #333;
        }
        .nav-link:hover {
            background-color: #e9ecef;
        }
        .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="mb-4">Blog Admin</h3>
                <div class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="posts.php">
                        <i class="bi bi-file-text"></i> Posts
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a class="nav-link active" href="comments.php">
                        <i class="bi bi-chat"></i> Comments
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <h2>All Comments</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th><th>User</th><th>Post</th><th>Content</th><th>Approved</th><th>Date</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?= htmlspecialchars($comment['id']) ?></td>
                            <td><?= htmlspecialchars($comment['username'] ?? 'Anonymous') ?></td>
                            <td><?= htmlspecialchars($comment['post_title']) ?></td>
                            <td><?= htmlspecialchars($comment['content']) ?></td>
                            <td><?= $comment['is_approved'] ? 'Yes' : 'No' ?></td>
                            <td><?= htmlspecialchars($comment['created_at']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                    <input type="hidden" name="delete_comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 