<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Enums\UserRole;
use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;

/** UC8: Manage Users and Categories */
final class AdminController
{
    public function __construct(
        private UserRepository $users,
        private CategoryRepository $categories,
        private View $view
    ) {}

    public function users(Request $r, array $p = []): Response
    {
        return $this->view->render('admin/users', ['users' => $this->users->all()]);
    }

    public function storeUser(Request $r, array $p = []): Response
    {
        $roles = implode(',', array_column(UserRole::cases(), 'value'));
        $v = Validator::make($r->input, [
            'name' => 'required|max:100', 'email' => 'required|email|max:150',
            'password' => 'required|min:8', 'role' => "required|in:{$roles}",
        ]);
        if (!$v->fails() && $this->users->findByEmail((string) $r->input('email'))) {
            flash('error', 'อีเมลนี้ถูกใช้แล้ว');
        } elseif ($v->fails()) {
            with_old($r->input);
            flash('error', $v->firstError());
        } else {
            $this->users->create([
                'name' => trim($r->input['name']), 'email' => trim($r->input['email']),
                'password_hash' => password_hash((string) $r->input('password'), PASSWORD_DEFAULT),
                'role' => $r->input['role'], 'line_user_id' => trim((string) $r->input('line_user_id')) ?: null,
            ]);
            flash('success', 'เพิ่มผู้ใช้เรียบร้อย');
        }
        return Response::redirect(url('/admin/users'));
    }

    public function updateUser(Request $r, array $p): Response
    {
        $roles = implode(',', array_column(UserRole::cases(), 'value'));
        $v = Validator::make($r->input, ['role' => "required|in:{$roles}"]);
        if ($v->fails() || !$this->users->find((int) $p['id'])) {
            flash('error', 'ข้อมูลไม่ถูกต้อง');
        } else {
            $this->users->update((int) $p['id'], [
                'role' => $r->input['role'],
                'line_user_id' => trim((string) $r->input('line_user_id')) ?: null,
            ]);
            flash('success', 'อัปเดตผู้ใช้เรียบร้อย');
        }
        return Response::redirect(url('/admin/users'));
    }

    public function categories(Request $r, array $p = []): Response
    {
        return $this->view->render('admin/categories', ['categories' => $this->categories->all()]);
    }

    public function storeCategory(Request $r, array $p = []): Response
    {
        $v = Validator::make($r->input, ['name' => 'required|max:100', 'description' => 'max:500']);
        if ($v->fails()) {
            flash('error', $v->firstError());
        } else {
            try {
                $this->categories->create(['name' => trim($r->input['name']), 'description' => trim((string) $r->input('description'))]);
                flash('success', 'เพิ่มหมวดหมู่เรียบร้อย');
            } catch (\PDOException) {
                flash('error', 'ชื่อหมวดหมู่ซ้ำ');
            }
        }
        return Response::redirect(url('/admin/categories'));
    }

    public function deleteCategory(Request $r, array $p): Response
    {
        try {
            $this->categories->delete((int) $p['id']);
            flash('success', 'ลบหมวดหมู่แล้ว');
        } catch (\PDOException) {
            flash('error', 'ลบไม่ได้ เพราะมี Ticket ใช้หมวดหมู่นี้อยู่');
        }
        return Response::redirect(url('/admin/categories'));
    }
}
