<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' | ' : '' ?><?= getenv('APP_NAME') ?: 'PHP Blog' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="/">Blog</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/">Anasayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="/about">Hakkında</a></li>
                    <li class="nav-item"><a class="nav-link" href="/contact">İletişim</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="/dashboard">Panel</a></li>
                        <li class="nav-item"><a class="nav-link" href="/logout">Çıkış</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/login">Giriş</a></li>
                        <li class="nav-item"><a class="nav-link" href="/register">Kayıt Ol</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mb-5">
        <?php if (isset($content)) echo $content; ?>
    </main>
    <footer class="bg-light text-center py-3">
        <div class="container">
            <small>&copy; <?= date('Y') ?> <?= getenv('APP_NAME') ?: 'PHP Blog' ?>. Tüm hakları saklıdır.</small>
        </div>
    </footer>
</body>
</html> 