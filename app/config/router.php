<?php

use App\Core\Router;

$router = new Router();

// Public routes
$router->add('', 'HomeController', 'index');
$router->add('about', 'HomeController', 'about');
$router->add('contact', 'HomeController', 'contact');
$router->add('search', 'HomeController', 'search');
$router->add('author/{username}', 'HomeController', 'author');
$router->add('tag/{tag}', 'HomeController', 'tag');

// Auth routes
$router->add('login', 'AuthController', 'login');
$router->add('register', 'AuthController', 'register');
$router->add('logout', 'AuthController', 'logout');
$router->add('profile', 'AuthController', 'profile');

// Post routes
$router->add('posts', 'PostController', 'index');
$router->add('posts/{slug}', 'PostController', 'show');
$router->add('posts/create', 'PostController', 'create');
$router->add('posts/edit/{id}', 'PostController', 'edit');
$router->add('posts/delete/{id}', 'PostController', 'delete');
$router->add('posts/{id}/comment', 'PostController', 'comment', 'POST');

// Dashboard routes
$router->add('dashboard', 'DashboardController', 'index');
$router->add('dashboard/posts', 'DashboardController', 'posts');
$router->add('dashboard/comments', 'DashboardController', 'comments');
$router->add('dashboard/users', 'DashboardController', 'users');
$router->add('dashboard/comments/{id}/approve', 'DashboardController', 'approveComment');
$router->add('dashboard/comments/{id}/delete', 'DashboardController', 'deleteComment');
$router->add('dashboard/users/{id}/toggle', 'DashboardController', 'toggleUserStatus');
$router->add('dashboard/users/{id}/role', 'DashboardController', 'updateUserRole', 'POST');

// Error routes
$router->add('404', 'HomeController', 'error404');
$router->add('500', 'HomeController', 'error500'); 