<h1 class="mb-4">Yeni Yazı Ekle</h1>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="title" class="form-label">Başlık</label>
        <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($old['title'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="content" class="form-label">İçerik</label>
        <textarea class="form-control" id="content" name="content" rows="8" required><?= htmlspecialchars($old['content'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label for="excerpt" class="form-label">Kısa Açıklama</label>
        <input type="text" class="form-control" id="excerpt" name="excerpt" value="<?= htmlspecialchars($old['excerpt'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="featured_image" class="form-label">Kapak Görseli</label>
        <input type="file" class="form-control" id="featured_image" name="featured_image">
    </div>
    <div class="mb-3">
        <label for="status" class="form-label">Durum</label>
        <select class="form-select" id="status" name="status">
            <option value="draft" <?= (isset($old['status']) && $old['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
            <option value="published" <?= (isset($old['status']) && $old['status'] === 'published') ? 'selected' : '' ?>>Yayınla</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="/posts" class="btn btn-secondary">İptal</a>
</form> 