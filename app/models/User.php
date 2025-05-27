<?php

namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected $table = 'users';
    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'bio',
        'avatar',
        'role',
        'is_active'
    ];

    public function authenticate($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }

        return false;
    }

    public function register($data) {
        // Validate email uniqueness
        if ($this->where('email', $data['email'])) {
            throw new \Exception('Email already exists');
        }

        // Validate username uniqueness
        if ($this->where('username', $data['username'])) {
            throw new \Exception('Username already exists');
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role
        $data['role'] = $data['role'] ?? 'user';
        $data['is_active'] = $data['is_active'] ?? true;

        return $this->create($data);
    }

    public function updateProfile($id, $data) {
        // Remove password from update if not provided
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Don't allow role update through profile update
        unset($data['role']);
        unset($data['is_active']);

        return $this->update($id, $data);
    }

    public function getPosts($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, u.avatar 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$userId, $perPage, $offset]);
        $items = $stmt->fetchAll();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = $stmt->fetchColumn();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function getComments($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT c.*, p.title as post_title, p.slug as post_slug 
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            WHERE c.user_id = ? 
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$userId, $perPage, $offset]);
        $items = $stmt->fetchAll();
        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = $stmt->fetchColumn();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
} 