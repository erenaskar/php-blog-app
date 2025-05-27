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

// Kullanıcı silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = (int)$_POST['delete_user_id'];
    if ($delete_id !== (int)$_SESSION['user_id']) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$delete_id]);
    }
    header('Location: users.php');
    exit;
}

$stmt = $pdo->query('SELECT * FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users - Admin</title>
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
                    <a class="nav-link active" href="users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a class="nav-link" href="comments.php">
                        <i class="bi bi-chat"></i> Comments
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <h2>All Users</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                                <?php elseif ($user['id'] == $_SESSION['user_id']): ?>
                                <span class="text-muted">(You)</span>
                                <?php elseif ($user['role'] === 'admin'): ?>
                                <span class="text-muted">(Admin)</span>
                                <?php endif; ?>
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