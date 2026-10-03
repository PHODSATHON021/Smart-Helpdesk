<h1 class="text-2xl font-bold text-blue-800 mb-4">จัดการหมวดหมู่</h1>

<form method="post" action="<?= url('/admin/categories') ?>" class="bg-white rounded shadow p-5 mb-6 flex flex-wrap gap-3 items-end text-sm">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div><label class="block mb-1">ชื่อหมวดหมู่</label><input name="name" required maxlength="100" class="border rounded px-3 py-2"></div>
  <div class="flex-1 min-w-[200px]"><label class="block mb-1">คำอธิบาย</label><input name="description" maxlength="500" class="w-full border rounded px-3 py-2"></div>
  <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">เพิ่ม</button>
</form>

<div class="bg-white rounded shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-100 text-left"><tr><th class="p-3">ชื่อ</th><th class="p-3">คำอธิบาย</th><th class="p-3">จำนวน Ticket</th><th class="p-3"></th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
      <tr class="border-t">
        <td class="p-3 font-medium"><?= e($c['name']) ?></td>
        <td class="p-3"><?= e($c['description']) ?></td>
        <td class="p-3"><?= (int) $c['ticket_count'] ?></td>
        <td class="p-3">
          <form method="post" action="<?= url('/admin/categories/' . $c['id'] . '/delete') ?>" onsubmit="return confirm('ลบหมวดหมู่นี้?')">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button class="px-3 py-1 bg-red-600 text-white rounded">ลบ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
