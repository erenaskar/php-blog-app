<?php

namespace App\Models;

use App\Core\Model;

class Post extends Model {
    protected $table = 'posts';
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'status',
        'published_at'
    ];

    public function create($data) {
        // Generate slug from title
        $data['slug'] = $this->generateSlug($data['title']);
        
        // Generate excerpt if not provided
        if (empty($data['excerpt'])) {
            $data['excerpt'] = $this->generateExcerpt($data['content']);
        }

        // Set published_at if status is published
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        return parent::create($data);
    }

    public function update($id, $data) {
        // Generate new slug if title is changed
        if (isset($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title'], $id);
        }

        // Generate excerpt if content is changed and excerpt is not provided
        if (isset($data['content']) && empty($data['excerpt'])) {
            $data['excerpt'] = $this->generateExcerpt($data['content']);
        }

        // Update published_at if status is changed to published
        if (isset($data['status']) && $data['status'] === 'published') {
            $post = $this->find($id);
            if ($post['status'] !== 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
        }

        return parent::update($id, $data);
    }

    public function getPublishedPosts($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, u.avatar, 
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND is_approved = 1) as comment_count
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.status = 'published' AND p.published_at <= NOW()
            ORDER BY p.published_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll();
        
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM posts 
            WHERE status = 'published' AND published_at <= NOW()
        ");
        $total = $stmt->fetchColumn();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function getPostBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, u.avatar, u.bio,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND is_approved = 1) as comment_count
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.slug = ? AND p.status = 'published' AND p.published_at <= NOW()
        ");
        
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function getRelatedPosts($postId, $limit = 3) {
        $post = $this->find($postId);
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.username
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id != ? 
            AND p.status = 'published' 
            AND p.published_at <= NOW()
            AND p.user_id = ?
            ORDER BY p.published_at DESC 
            LIMIT ?
        ");
        
        $stmt->execute([$postId, $post['user_id'], $limit]);
        return $stmt->fetchAll();
    }

    public function search($query, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $search = "%{$query}%";
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, u.avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.status = 'published' 
            AND p.published_at <= NOW()
            AND (p.title LIKE ? OR p.content LIKE ?)
            ORDER BY p.published_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$search, $search, $perPage, $offset]);
        $items = $stmt->fetchAll();
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM posts 
            WHERE status = 'published' 
            AND published_at <= NOW()
            AND (title LIKE ? OR content LIKE ?)
        ");
        
        $stmt->execute([$search, $search]);
        $total = $stmt->fetchColumn();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    private function generateSlug($title, $excludeId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $excludeId]);
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    private function generateExcerpt($content, $length = 160) {
        $excerpt = strip_tags($content);
        if (strlen($excerpt) > $length) {
            $excerpt = substr($excerpt, 0, $length) . '...';
        }
        return $excerpt;
    }
} 