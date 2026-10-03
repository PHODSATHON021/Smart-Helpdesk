<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public function __construct(private string $viewPath, private Auth $auth, private Csrf $csrf) {}

    public function render(string $view, array $data = [], int $status = 200, string $layout = 'layouts/main'): Response
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        // old input ใช้ได้ภายใน request ถัดไป 1 ครั้ง
        $_SESSION['_old_current'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);

        $shared = [
            'authUser' => $this->auth->user(),
            'csrf'     => $this->csrf->token(),
            'flashes'  => $flashes,
        ];

        $content = $this->capture($view, $data + $shared);
        $html    = $this->capture($layout, $shared + $data + ['content' => $content]);
        return Response::html($html, $status);
    }

    private function capture(string $template, array $vars): string
    {
        $file = $this->viewPath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}
