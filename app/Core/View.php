<?php

namespace App\Core;

class View {
    private $layout = 'default';

    public function setLayout($layout) {
        $this->layout = $layout;
    }

    public function render($view, $data = []) {
        // Extract data to make variables available in view
        extract($data);

        // Start output buffering
        ob_start();

        // Include the view file
        $viewFile = BASE_PATH . '/app/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new \Exception("View file not found: {$viewFile}");
        }

        // Get the contents of the buffer
        $content = ob_get_clean();

        // Include the layout
        $layoutFile = BASE_PATH . '/app/views/layouts/' . $this->layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            throw new \Exception("Layout file not found: {$layoutFile}");
        }
    }

    public function partial($view, $data = []) {
        extract($data);
        $viewFile = BASE_PATH . '/app/views/partials/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new \Exception("Partial view file not found: {$viewFile}");
        }
    }

    public function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public function asset($path) {
        return '/public/' . ltrim($path, '/');
    }

    public function url($path) {
        return '/' . ltrim($path, '/');
    }
} 