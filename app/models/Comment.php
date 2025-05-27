<?php

namespace App\Models;

use App\Core\Model;

class Comment extends Model {
    protected $table = 'comments';
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
        'is_approved'
    ];

    public function getPostComments($postId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.avatar,
                   (SELECT COUNT(*) FROM comments WHERE parent_id = c.id AND is_approved = 1) as reply_count
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? AND c.parent_id IS NULL AND c.is_approved = 1
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$postId, $perPage, $offset]);
        $items = $stmt->fetchAll();
        
        // Get replies for each comment
        foreach ($items as &$comment) {
            $comment['replies'] = $this->getCommentReplies($comment['id']);
        }
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM comments 
            WHERE post_id = ? AND parent_id IS NULL AND is_approved = 1
        ");
        
        $stmt->execute([$postId]);
        $total = $stmt->fetchColumn();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function getCommentReplies($commentId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.avatar
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.parent_id = ? AND c.is_approved = 1
            ORDER BY c.created_at ASC
        ");
        
        $stmt->execute([$commentId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        // Set default approval status
        if (!isset($data['is_approved'])) {
            $data['is_approved'] = false;
        }

        // If user is logged in, set user_id
        if (isset($_SESSION['user_id'])) {
            $data['user_id'] = $_SESSION['user_id'];
            // Auto-approve comments from logged-in users
            $data['is_approved'] = true;
        }

        return parent::create($data);
    }

    public function approve($id) {
        return $this->update($id, ['is_approved' => true]);
    }

    public function getUnapprovedComments($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT c.*, p.title as post_title, p.slug as post_slug, u.username
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.is_approved = 0
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll();
        
        $stmt = $this->db->query("SELECT COUNT(*) FROM comments WHERE is_approved = 0");
        $total = $stmt->fetchColumn();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function getCommentCount($postId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM comments 
            WHERE post_id = ? AND is_approved = 1
        ");
        
        $stmt->execute([$postId]);
        return $stmt->fetchColumn();
    }
} 