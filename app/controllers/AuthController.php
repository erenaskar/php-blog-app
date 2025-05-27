<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->csrf();

                $errors = $this->validateRequest([
                    'email' => 'required|email',
                    'password' => 'required|min:6'
                ]);

                if (!empty($errors)) {
                    return $this->render('auth/login', [
                        'errors' => $errors,
                        'old' => $_POST
                    ]);
                }

                $user = $this->userModel->authenticate($_POST['email'], $_POST['password']);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    $this->redirect('/dashboard');
                } else {
                    return $this->render('auth/login', [
                        'error' => 'Invalid email or password',
                        'old' => $_POST
                    ]);
                }
            } catch (\Exception $e) {
                return $this->render('auth/login', [
                    'error' => $e->getMessage(),
                    'old' => $_POST
                ]);
            }
        }

        return $this->render('auth/login');
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->csrf();

                $errors = $this->validateRequest([
                    'username' => 'required|min:3|max:50',
                    'email' => 'required|email',
                    'password' => 'required|min:6',
                    'password_confirmation' => 'required'
                ]);

                if ($_POST['password'] !== $_POST['password_confirmation']) {
                    $errors['password_confirmation'] = 'Passwords do not match';
                }

                if (!empty($errors)) {
                    return $this->render('auth/register', [
                        'errors' => $errors,
                        'old' => $_POST
                    ]);
                }

                $user = $this->userModel->register([
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'full_name' => $_POST['full_name'] ?? null
                ]);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];

                $this->redirect('/dashboard');
            } catch (\Exception $e) {
                return $this->render('auth/register', [
                    'error' => $e->getMessage(),
                    'old' => $_POST
                ]);
            }
        }

        return $this->render('auth/register');
    }

    public function logout() {
        session_destroy();
        $this->redirect('/login');
    }

    public function profile() {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->csrf();

                $errors = $this->validateRequest([
                    'username' => 'required|min:3|max:50',
                    'email' => 'required|email',
                    'full_name' => 'max:100',
                    'bio' => 'max:500'
                ]);

                if (!empty($errors)) {
                    return $this->render('auth/profile', [
                        'user' => $user,
                        'errors' => $errors
                    ]);
                }

                $data = [
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'full_name' => $_POST['full_name'],
                    'bio' => $_POST['bio']
                ];

                // Handle password change if provided
                if (!empty($_POST['password'])) {
                    if (strlen($_POST['password']) < 6) {
                        $errors['password'] = 'Password must be at least 6 characters';
                        return $this->render('auth/profile', [
                            'user' => $user,
                            'errors' => $errors
                        ]);
                    }

                    if ($_POST['password'] !== $_POST['password_confirmation']) {
                        $errors['password_confirmation'] = 'Passwords do not match';
                        return $this->render('auth/profile', [
                            'user' => $user,
                            'errors' => $errors
                        ]);
                    }

                    $data['password'] = $_POST['password'];
                }

                // Handle avatar upload
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = BASE_PATH . '/public/uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($fileExtension, $allowedExtensions)) {
                        $errors['avatar'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions);
                        return $this->render('auth/profile', [
                            'user' => $user,
                            'errors' => $errors
                        ]);
                    }

                    $fileName = uniqid('avatar_') . '.' . $fileExtension;
                    $uploadFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
                        // Delete old avatar if exists
                        if (!empty($user['avatar'])) {
                            $oldAvatar = BASE_PATH . '/public' . $user['avatar'];
                            if (file_exists($oldAvatar)) {
                                unlink($oldAvatar);
                            }
                        }
                        $data['avatar'] = '/uploads/avatars/' . $fileName;
                    }
                }

                $this->userModel->updateProfile($user['id'], $data);
                $this->redirect('/profile?success=1');
            } catch (\Exception $e) {
                return $this->render('auth/profile', [
                    'user' => $user,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->render('auth/profile', [
            'user' => $user
        ]);
    }
} 