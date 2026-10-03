<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <title>เข้าสู่ระบบ — Smart IT Helpdesk</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{font-family:'Sarabun','Segoe UI',sans-serif}</style>
</head>
<body class="bg-gradient-to-br from-blue-700 to-indigo-900 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <?php foreach ($flashes as $f): ?>
      <div class="mb-3 p-3 rounded text-sm <?= $f['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
</body>
</html>
