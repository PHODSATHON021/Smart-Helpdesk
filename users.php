<?php use App\Enums\UserRole; ?>
<h1 class="text-2xl font-bold text-blue-800 mb-4">จัดการผู้ใช้</h1>

<form method="post" action="<?= url('/admin/users') ?>" class="bg-white rounded shadow p-5 mb-6 grid md:grid-cols-6 gap-3 text-sm items-end">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div class="md:col-span-1"><label class="block mb-1">ชื่อ</label><input name="name" required class="w-full border rounded px-2 py-2"></div>
  <div class="md:col-span-1"><label class="block mb-1">อีเมล</label><input type="email" name="email" required class="w-full border rounded px-2 py-2"></div>
  <div><label class="block mb-1">รหัสผ่าน (≥8)</label><input type="password" name="password" required minlength="8" class="w-full border rounded px-2 py-2"></div>
  <div><label class="block mb-1">บทบาท</label>
    <select name="role" class="w-full border rounded px-2 py-2">
      <?php foreach (UserRole::cases() as $r): ?><option value="<?= $r->value ?>"><?= e($r->label()) ?></option><?php endforeach; ?>
    </select></div>
  <div><label class="block mb-1">LINE userId</label><input name="line_user_id" class="w-full border rounded px-2 py-2"></div>
  <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">เพิ่มผู้ใช้</button>
</form>

<div class="bg-white rounded shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-100 text-left"><tr><th class="p-3">ชื่อ</th><th class="p-3">อีเมล</th><th class="p-3">บทบาท</th><th class="p-3">LINE userId</th><th class="p-3"></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $fid = 'uf' . (int) $u['id']; ?>
      <tr class="border-t">
        <td class="p-3"><?= e($u['name']) ?></td>
        <td class="p-3"><?= e($u['email']) ?></td>
        <td class="p-3">
          <select name="role" form="<?= $fid ?>" class="border rounded px-2 py-1">
            <?php foreach (UserRole::cases() as $r): ?>
              <option value="<?= $r->value ?>" <?= $u['role'] === $r->value ? 'selected' : '' ?>><?= e($r->label()) ?></option>
            <?php endforeach; ?>
          </select></td>
        <td class="p-3"><input name="line_user_id" form="<?= $fid ?>" value="<?= e($u['line_user_id']) ?>" class="border rounded px-2 py-1 w-56"></td>
        <td class="p-3">
          <form id="<?= $fid ?>" method="post" action="<?= url('/admin/users/' . $u['id']) ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button class="px-3 py-1 bg-slate-700 text-white rounded">บันทึก</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
