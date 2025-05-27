<h1 class="mb-4">Son Yazılar</h1>
<?php if (!empty($posts['items'])): ?>
    <div class="row">
        <?php foreach ($posts['items'] as $post): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <?php if (!empty($post['featured_image'])): ?>
                        <img src="<?= $post['featured_image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="/posts/<?= htmlspecialchars($post['slug']) ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h5>
                        <p class="card-text small text-muted mb-2">
                            <?= htmlspecialchars($post['username']) ?> | <?= date('d.m.Y', strtotime($post['published_at'])) ?>
                        </p>
                        <p class="card-text">
                            <?= htmlspecialchars($post['excerpt']) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">Henüz yazı eklenmemiş.</div>
<?php endif; ?> 