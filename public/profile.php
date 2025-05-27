<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!empty($password) && $password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Kullanıcı adı veya e-posta başka kullanıcıya ait mi?
        $stmt = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?');
        $stmt->execute([$username, $email, $user['id']]);
        if ($stmt->fetch()) {
            $error = 'This username or email is already taken.';
        } else {
            $updateFields = [
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name
            ];
            if (!empty($password)) {
                $updateFields['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $setSql = '';
            $params = [];
            foreach ($updateFields as $key => $val) {
                $setSql .= "$key = ?, ";
                $params[] = $val;
            }
            $setSql = rtrim($setSql, ', ');
            $params[] = $user['id'];
            $stmt = $pdo->prepare("UPDATE users SET $setSql WHERE id = ?");
            $result = $stmt->execute($params);
            if ($result) {
                $success = 'Your profile has been updated.';
                // Güncel bilgileri tekrar çek
                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Update failed.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Blog</title>
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
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container my-5" style="max-width:600px;">
        <h2 class="mb-4">My Profile</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 