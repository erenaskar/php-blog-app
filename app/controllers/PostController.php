<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Comment;

class PostController extends Controller {
    private $postModel;
    private $commentModel;

    public function __construct() {
        parent::__construct();
        $this->postModel = new Post();
        $this->commentModel = new Comment();
    }

    public function index() {
        $page = $_GET['page'] ?? 1;
        $posts = $this->postModel->getPublishedPosts($page);
        
        return $this->render('posts/index', [
            'posts' => $posts
        ]);
    }

    public function show($slug) {
        $post = $this->postModel->getPostBySlug($slug);
        
        if (!$post) {
            throw new \Exception('Post not found', 404);
        }

        $comments = $this->commentModel->getPostComments($post['id']);
        $relatedPosts = $this->postModel->getRelatedPosts($post['id']);

        return $this->render('posts/show', [
            'post' => $post,
            'comments' => $comments,
            'relatedPosts' => $relatedPosts
        ]);
    }

    public function create() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->csrf();

                $errors = $this->validateRequest([
                    'title' => 'required|min:3|max:255',
                    'content' => 'required|min:10',
                    'status' => 'required'
                ]);

                if (!empty($errors)) {
                    return $this->render('posts/create', [
                        'errors' => $errors,
                        'old' => $_POST
                    ]);
                }

                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'excerpt' => $_POST['excerpt'] ?? null,
                    'status' => $_POST['status']
                ];

                // Handle featured image upload
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = BASE_PATH . '/public/uploads/posts/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileExtension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($fileExtension, $allowedExtensions)) {
                        $errors['featured_image'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions);
                        return $this->render('posts/create', [
                            'errors' => $errors,
                            'old' => $_POST
                        ]);
                    }

                    $fileName = uniqid('post_') . '.' . $fileExtension;
                    $uploadFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadFile)) {
                        $data['featured_image'] = '/uploads/posts/' . $fileName;
                    }
                }

                $post = $this->postModel->create($data);
                $this->redirect('/posts/' . $post['slug']);
            } catch (\Exception $e) {
                return $this->render('posts/create', [
                    'error' => $e->getMessage(),
                    'old' => $_POST
                ]);
            }
        }

        return $this->render('posts/create');
    }

    public function edit($id) {
        $this->requireAuth();
        
        $post = $this->postModel->find($id);
        
        if (!$post) {
            throw new \Exception('Post not found', 404);
        }

        // Check if user is authorized to edit this post
        if ($post['user_id'] !== $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
            throw new \Exception('Unauthorized', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->csrf();

                $errors = $this->validateRequest([
                    'title' => 'required|min:3|max:255',
                    'content' => 'required|min:10',
                    'status' => 'required'
                ]);

                if (!empty($errors)) {
                    return $this->render('posts/edit', [
                        'post' => $post,
                        'errors' => $errors
                    ]);
                }

                $data = [
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'excerpt' => $_POST['excerpt'] ?? null,
                    'status' => $_POST['status']
                ];

                // Handle featured image upload
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = BASE_PATH . '/public/uploads/posts/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileExtension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($fileExtension, $allowedExtensions)) {
                        $errors['featured_image'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions);
                        return $this->render('posts/edit', [
                            'post' => $post,
                            'errors' => $errors
                        ]);
                    }

                    $fileName = uniqid('post_') . '.' . $fileExtension;
                    $uploadFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadFile)) {
                        // Delete old featured image if exists
                        if (!empty($post['featured_image'])) {
                            $oldImage = BASE_PATH . '/public' . $post['featured_image'];
                            if (file_exists($oldImage)) {
                                unlink($oldImage);
                            }
                        }
                        $data['featured_image'] = '/uploads/posts/' . $fileName;
                    }
                }

                $this->postModel->update($id, $data);
                $this->redirect('/posts/' . $post['slug']);
            } catch (\Exception $e) {
                return $this->render('posts/edit', [
                    'post' => $post,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->render('posts/edit', [
            'post' => $post
        ]);
    }

    public function delete($id) {
        $this->requireAuth();
        
        $post = $this->postModel->find($id);
        
        if (!$post) {
            throw new \Exception('Post not found', 404);
        }

        // Check if user is authorized to delete this post
        if ($post['user_id'] !== $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
            throw new \Exception('Unauthorized', 403);
        }

        // Delete featured image if exists
        if (!empty($post['featured_image'])) {
            $imagePath = BASE_PATH . '/public' . $post['featured_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $this->postModel->delete($id);
        $this->redirect('/dashboard');
    }

    public function comment($postId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new \Exception('Method not allowed', 405);
        }

        try {
            $this->csrf();

            $errors = $this->validateRequest([
                'content' => 'required|min:3|max:1000'
            ]);

            if (!empty($errors)) {
                return $this->json(['errors' => $errors], 422);
            }

            $post = $this->postModel->find($postId);
            if (!$post) {
                throw new \Exception('Post not found', 404);
            }

            $data = [
                'post_id' => $postId,
                'content' => $_POST['content'],
                'parent_id' => $_POST['parent_id'] ?? null
            ];

            $comment = $this->commentModel->create($data);
            
            if ($comment['is_approved']) {
                return $this->json([
                    'message' => 'Comment added successfully',
                    'comment' => $comment
                ]);
            } else {
                return $this->json([
                    'message' => 'Comment submitted and waiting for approval'
                ]);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function search() {
        $query = $_GET['q'] ?? '';
        $page = $_GET['page'] ?? 1;

        if (empty($query)) {
            return $this->redirect('/');
        }

        $results = $this->postModel->search($query, $page);
        
        return $this->render('posts/search', [
            'query' => $query,
            'results' => $results
        ]);
    }
} 