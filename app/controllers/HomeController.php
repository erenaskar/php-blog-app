<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

class HomeController extends Controller {
    private $postModel;

    public function __construct() {
        parent::__construct();
        $this->postModel = new Post();
    }

    public function index() {
        $page = $_GET['page'] ?? 1;
        $posts = $this->postModel->getPublishedPosts($page);
        
        return $this->render('home/index', [
            'posts' => $posts
        ]);
    }

    public function about() {
        return $this->render('home/about');
    }

    public function contact() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->csrf();

                $errors = $this->validateRequest([
                    'name' => 'required|min:3|max:100',
                    'email' => 'required|email',
                    'subject' => 'required|min:3|max:200',
                    'message' => 'required|min:10|max:1000'
                ]);

                if (!empty($errors)) {
                    return $this->render('home/contact', [
                        'errors' => $errors,
                        'old' => $_POST
                    ]);
                }

                // Here you would typically send an email
                // For now, we'll just redirect with a success message
                $this->redirect('/contact?success=1');
            } catch (\Exception $e) {
                return $this->render('home/contact', [
                    'error' => $e->getMessage(),
                    'old' => $_POST
                ]);
            }
        }

        return $this->render('home/contact');
    }

    public function search() {
        $query = $_GET['q'] ?? '';
        $page = $_GET['page'] ?? 1;

        if (empty($query)) {
            return $this->redirect('/');
        }

        $results = $this->postModel->search($query, $page);
        
        return $this->render('home/search', [
            'query' => $query,
            'results' => $results
        ]);
    }

    public function author($username) {
        $userModel = new \App\Models\User();
        $user = $userModel->findByUsername($username);
        
        if (!$user) {
            throw new \Exception('Author not found', 404);
        }

        $page = $_GET['page'] ?? 1;
        $posts = $userModel->getPosts($user['id'], $page);
        
        return $this->render('home/author', [
            'author' => $user,
            'posts' => $posts
        ]);
    }

    public function tag($tag) {
        $tagModel = new \App\Models\Tag();
        $tag = $tagModel->findBySlug($tag);
        
        if (!$tag) {
            throw new \Exception('Tag not found', 404);
        }

        $page = $_GET['page'] ?? 1;
        $posts = $tagModel->getPosts($tag['id'], $page);
        
        return $this->render('home/tag', [
            'tag' => $tag,
            'posts' => $posts
        ]);
    }

    public function error404() {
        http_response_code(404);
        return $this->render('errors/404');
    }

    public function error500() {
        http_response_code(500);
        return $this->render('errors/500');
    }
} 