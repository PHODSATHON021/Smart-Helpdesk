<?php
use App\Enums\{TicketPriority, TicketStatus};
$role = $authUser['role'];
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <h1 class="text-2xl font-bold text-blue-800">
    <?= $role === 'user' ? 'Ticket ของฉัน' : 'Ticket ทั้งหมด' ?>
  </h1>
  <?php if ($role === 'user'): ?>
    <a href="<?= url('/tickets/create') ?>" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">+ แจ้งซ่อมใหม่</a>
  <?php endif; ?>
</div>

<form method="get" class="bg-white p-4 rounded shadow mb-4 flex flex-wrap gap-3 items-end text-sm">
  <div><label class="block mb-1">ค้นหา</label>
    <input name="q" value="<?= e($keyword) ?>" placeholder="หัวข้อ / รายละเอียด / เลข #" class="border rounded px-3 py-2 w-56"></div>
  <div><label class="block mb-1">สถานะ</label>
    <select name="status" class="border rounded px-3 py-2">
      <option value="">ทั้งหมด</option>
      <?php foreach (TicketStatus::cases() as $s): ?>
        <option value="<?= $s->value ?>" <?= ($filters['status'] ?? '') === $s->value ? 'selected' : '' ?>><?= e($s->label()) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div><label class="block mb-1">ความเร่งด่วน</label>
    <select name="priority" class="border rounded px-3 py-2">
      <option value="">ทั้งหมด</option>
      <?php foreach (TicketPriority::cases() as $p): ?>
        <option value="<?= $p->value ?>" <?= ($filters['priority'] ?? '') === $p->value ? 'selected' : '' ?>><?= e($p->label()) ?></option>
      <?php endforeach; ?>
    </select></div>
  <?php if ($role === 'technician'): ?>
    <label class="flex items-center gap-2 pb-2"><input type="checkbox" name="mine" value="1" <?= $mine ? 'checked' : '' ?>> เฉพาะงานของฉัน</label>
  <?php endif; ?>
  <button class="px-4 py-2 bg-slate-700 text-white rounded">ค้นหา</button>
</form>

<div class="bg-white rounded shadow overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-100 text-left">
      <tr><th class="p-3">#</th><th class="p-3">หัวข้อ</th><th class="p-3">หมวด</th><th class="p-3">ผู้แจ้ง</th>
          <th class="p-3">ช่าง</th><th class="p-3">เร่งด่วน</th><th class="p-3">สถานะ</th><th class="p-3">วันที่</th></tr>
    </thead>
    <tbody>
    <?php foreach ($tickets as $t):
        $st = TicketStatus::from($t['status']); $pr = TicketPriority::from($t['priority']); ?>
      <tr class="border-t hover:bg-slate-50">
        <td class="p-3"><?= (int) $t['id'] ?></td>
        <td class="p-3"><a class="text-blue-700 hover:underline font-medium" href="<?= url('/tickets/' . $t['id']) ?>"><?= e($t['title']) ?></a></td>
        <td class="p-3"><?= e($t['category_name']) ?></td>
        <td class="p-3"><?= e($t['owner_name']) ?></td>
        <td class="p-3"><?= e($t['technician_name'] ?? '-') ?></td>
        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs <?= $pr->badgeClass() ?>"><?= e($pr->label()) ?></span></td>
        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs <?= $st->badgeClass() ?>"><?= e($st->label()) ?></span></td>
        <td class="p-3 whitespace-nowrap"><?= format_dt($t['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$tickets): ?><tr><td colspan="8" class="p-8 text-center text-slate-500">ไม่พบ Ticket</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
