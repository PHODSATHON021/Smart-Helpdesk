<?php use App\Enums\TicketPriority; ?>
<div class="max-w-2xl mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold text-blue-800 mb-4">แจ้งซ่อม / ขอความช่วยเหลือด้าน IT</h1>
  <form method="post" action="<?= url('/tickets') ?>" enctype="multipart/form-data" data-ajax data-action="create" class="space-y-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <div data-errors class="hidden p-3 bg-red-50 border border-red-300 text-red-800 text-sm rounded"></div>
    <div>
      <label class="block text-sm mb-1">หัวข้อ *</label>
      <input name="title" value="<?= e(old('title')) ?>" required minlength="5" maxlength="150" class="w-full border rounded px-3 py-2">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">หมวดหมู่ *</label>
        <select name="category_id" required class="w-full border rounded px-3 py-2">
          <option value="">-- เลือก --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) old('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">ความเร่งด่วน *</label>
        <select name="priority" class="w-full border rounded px-3 py-2">
          <?php foreach (TicketPriority::cases() as $p): ?>
            <option value="<?= $p->value ?>" <?= $p === TicketPriority::Medium ? 'selected' : '' ?>><?= e($p->label()) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-sm mb-1">รายละเอียดปัญหา *</label>
      <textarea name="description" rows="5" required minlength="10" class="w-full border rounded px-3 py-2"><?= e(old('description')) ?></textarea>
    </div>
    <div>
      <label class="block text-sm mb-1">แนบรูปภาพ (JPG/PNG/WEBP ไม่เกิน 2MB)</label>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm">
    </div>
    <button class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded disabled:opacity-50">ส่งเรื่องแจ้งซ่อม</button>
  </form>
</div>
