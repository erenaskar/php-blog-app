<h1 class="mb-4">Yazı Yönetimi</h1>
<a href="/posts/create" class="btn btn-success mb-3">Yeni Yazı Ekle</a>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Başlık</th>
            <th>Yazar</th>
            <th>Durum</th>
            <th>Yayın Tarihi</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($posts['items'])): ?>
            <?php foreach ($posts['items'] as $post): ?>
                <tr>
                    <td><?= htmlspecialchars($post['id']) ?></td>
                    <td><?= htmlspecialchars($post['title']) ?></td>
                    <td><?= htmlspecialchars($post['username']) ?></td>
                    <td><?= htmlspecialchars($post['status']) ?></td>
                    <td><?= !empty($post['published_at']) ? date('d.m.Y H:i', strtotime($post['published_at'])) : '-' ?></td>
                    <td>
                        <a href="/posts/edit/<?= $post['id'] ?>" class="btn btn-sm btn-primary">Düzenle</a>
                        <a href="/posts/delete/<?= $post['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" class="text-center">Henüz yazı yok.</td></tr>
        <?php endif; ?>
    </tbody>
</table> 