<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;

class DashboardController extends Controller {
    private $postModel;
    private $commentModel;
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->postModel = new Post();
        $this->commentModel = new Comment();
        $this->userModel = new User();
    }

    public function index() {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        
        if ($user['role'] === 'admin') {
            return $this->adminDashboard();
        }
        
        return $this->userDashboard($user);
    }

    private function userDashboard($user) {
        $page = $_GET['page'] ?? 1;
        $posts = $this->userModel->getPosts($user['id'], $page);
        $comments = $this->userModel->getComments($user['id'], 1, 5);
        
        return $this->render('dashboard/user', [
            'user' => $user,
            'posts' => $posts,
            'comments' => $comments
        ]);
    }

    private function adminDashboard() {
        // Get statistics
        $stats = [
            'total_posts' => $this->postModel->count(),
            'total_users' => $this->userModel->count(),
            'total_comments' => $this->commentModel->count(),
            'pending_comments' => $this->commentModel->countUnapproved()
        ];

        // Get recent posts
        $recentPosts = $this->postModel->getRecentPosts(5);
        
        // Get pending comments
        $pendingComments = $this->commentModel->getUnapprovedComments(1, 5);
        
        // Get recent users
        $recentUsers = $this->userModel->getRecentUsers(5);
        
        return $this->render('dashboard/admin', [
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'pendingComments' => $pendingComments,
            'recentUsers' => $recentUsers
        ]);
    }

    public function posts() {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        $page = $_GET['page'] ?? 1;
        
        if ($user['role'] === 'admin') {
            $posts = $this->postModel->getAllPosts($page);
        } else {
            $posts = $this->userModel->getPosts($user['id'], $page);
        }
        
        return $this->render('dashboard/posts', [
            'posts' => $posts,
            'isAdmin' => $user['role'] === 'admin'
        ]);
    }

    public function comments() {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        $page = $_GET['page'] ?? 1;
        
        if ($user['role'] === 'admin') {
            $comments = $this->commentModel->getAllComments($page);
        } else {
            $comments = $this->userModel->getComments($user['id'], $page);
        }
        
        return $this->render('dashboard/comments', [
            'comments' => $comments,
            'isAdmin' => $user['role'] === 'admin'
        ]);
    }

    public function users() {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        
        if ($user['role'] !== 'admin') {
            throw new \Exception('Unauthorized', 403);
        }
        
        $page = $_GET['page'] ?? 1;
        $users = $this->userModel->getAllUsers($page);
        
        return $this->render('dashboard/users', [
            'users' => $users
        ]);
    }

    public function approveComment($id) {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        
        if ($user['role'] !== 'admin') {
            throw new \Exception('Unauthorized', 403);
        }
        
        $comment = $this->commentModel->find($id);
        
        if (!$comment) {
            throw new \Exception('Comment not found', 404);
        }
        
        $this->commentModel->approve($id);
        $this->redirect('/dashboard/comments?success=1');
    }

    public function deleteComment($id) {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        $comment = $this->commentModel->find($id);
        
        if (!$comment) {
            throw new \Exception('Comment not found', 404);
        }
        
        // Check if user is authorized to delete this comment
        if ($user['role'] !== 'admin' && $comment['user_id'] !== $user['id']) {
            throw new \Exception('Unauthorized', 403);
        }
        
        $this->commentModel->delete($id);
        $this->redirect('/dashboard/comments?success=1');
    }

    public function toggleUserStatus($id) {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        
        if ($user['role'] !== 'admin') {
            throw new \Exception('Unauthorized', 403);
        }
        
        $targetUser = $this->userModel->find($id);
        
        if (!$targetUser) {
            throw new \Exception('User not found', 404);
        }
        
        // Prevent deactivating the last admin
        if ($targetUser['role'] === 'admin' && !$targetUser['is_active']) {
            $adminCount = $this->userModel->countAdmins();
            if ($adminCount <= 1) {
                throw new \Exception('Cannot deactivate the last admin user');
            }
        }
        
        $this->userModel->update($id, [
            'is_active' => !$targetUser['is_active']
        ]);
        
        $this->redirect('/dashboard/users?success=1');
    }

    public function updateUserRole($id) {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        
        if ($user['role'] !== 'admin') {
            throw new \Exception('Unauthorized', 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new \Exception('Method not allowed', 405);
        }
        
        try {
            $this->csrf();
            
            $targetUser = $this->userModel->find($id);
            
            if (!$targetUser) {
                throw new \Exception('User not found', 404);
            }
            
            // Prevent changing role of the last admin
            if ($targetUser['role'] === 'admin' && $_POST['role'] !== 'admin') {
                $adminCount = $this->userModel->countAdmins();
                if ($adminCount <= 1) {
                    throw new \Exception('Cannot change role of the last admin user');
                }
            }
            
            $this->userModel->update($id, [
                'role' => $_POST['role']
            ]);
            
            $this->redirect('/dashboard/users?success=1');
        } catch (\Exception $e) {
            return $this->render('dashboard/users', [
                'error' => $e->getMessage()
            ]);
        }
    }
} 