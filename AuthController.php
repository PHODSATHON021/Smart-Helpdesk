<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;

final class AuthController
{
    public function __construct(private Auth $auth, private View $view) {}

    public function showLogin(Request $r, array $p = []): Response
    {
        return $this->view->render('auth/login', [], 200, 'layouts/guest');
    }

    public function login(Request $r, array $p = []): Response
    {
        $v = Validator::make($r->input, ['email' => 'required|email', 'password' => 'required']);
        if ($v->fails() || !$this->auth->login((string) $r->input('email'), (string) $r->input('password'))) {
            with_old(['email' => $r->input('email')]);
            flash('error', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
            return Response::redirect(url('/login'));
        }
        return Response::redirect(url($this->auth->role() === 'admin' ? '/admin/dashboard' : '/tickets'));
    }

    public function logout(Request $r, array $p = []): Response
    {
        $this->auth->logout();
        session_start();                 // เริ่ม session ใหม่เพื่อเก็บ flash
        flash('success', 'ออกจากระบบแล้ว');
        return Response::redirect(url('/login'));
    }
}
