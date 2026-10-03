<?php
use App\Enums\UserRole;
$role = $authUser['role'] ?? null;
$nav = [
    ['/tickets', 'Tickets', ['user', 'technician', 'admin']],
    ['/tickets/create', '+ แจ้งซ่อม', ['user']],
    ['/admin/dashboard', 'Dashboard', ['admin']],
    ['/admin/users', 'ผู้ใช้', ['admin']],
    ['/admin/categories', 'หมวดหมู่', ['admin']],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <title><?= e($title ?? 'Smart IT Helpdesk') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{font-family:'Sarabun','Segoe UI',sans-serif}</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white shadow">
  <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
    <a href="<?= url('/') ?>" class="text-lg font-bold">🛠️ Smart IT Helpdesk</a>
    <nav class="flex flex-wrap items-center gap-1 text-sm">
      <?php foreach ($nav as [$path, $label, $roles]): if (!in_array($role, $roles, true)) continue; ?>
        <a href="<?= url($path) ?>" class="px-3 py-1 rounded hover:bg-white/20"><?= e($label) ?></a>
      <?php endforeach; ?>
      <?php if ($authUser): ?>
        <span class="ml-3 text-blue-100"><?= e($authUser['name']) ?> (<?= e(UserRole::from($role)->label()) ?>)</span>
        <form method="post" action="<?= url('/logout') ?>" class="inline">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button class="ml-2 px-3 py-1 rounded bg-white/20 hover:bg-white/30">ออกจากระบบ</button>
        </form>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6">
  <?php foreach ($flashes as $f): ?>
    <div class="mb-4 p-3 rounded border text-sm <?= $f['type'] === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800' ?>">
      <?= e($f['message']) ?>
    </div>
  <?php endforeach; ?>
  <div id="toast" class="hidden mb-4 p-3 rounded border text-sm"></div>
  <?= $content ?>
</main>

<script>window.APP = { base: <?= json_encode(url('/')) ?> };</script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
