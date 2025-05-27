<?php

namespace App\Core;

abstract class Controller {
    protected $db;
    protected $view;

    public function __construct() {
        $this->db = \App\Config\Database::getInstance()->getConnection();
        $this->view = new View();
    }

    protected function render($view, $data = []) {
        return $this->view->render($view, $data);
    }

    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }

    protected function json($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function validateRequest($rules) {
        $errors = [];
        $data = $_REQUEST;

        foreach ($rules as $field => $rule) {
            if (strpos($rule, 'required') !== false && empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }

            if (!empty($data[$field])) {
                if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Invalid email format';
                }
                if (strpos($rule, 'min:') !== false) {
                    preg_match('/min:(\d+)/', $rule, $matches);
                    $min = $matches[1];
                    if (strlen($data[$field]) < $min) {
                        $errors[$field] = ucfirst($field) . " must be at least {$min} characters";
                    }
                }
                if (strpos($rule, 'max:') !== false) {
                    preg_match('/max:(\d+)/', $rule, $matches);
                    $max = $matches[1];
                    if (strlen($data[$field]) > $max) {
                        $errors[$field] = ucfirst($field) . " must not exceed {$max} characters";
                    }
                }
            }
        }

        return $errors;
    }

    protected function csrf() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('CSRF token validation failed');
            }
        }

        return $_SESSION['csrf_token'];
    }

    protected function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    protected function getCurrentUser() {
        if ($this->isAuthenticated()) {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }
} 