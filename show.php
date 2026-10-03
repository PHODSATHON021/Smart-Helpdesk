<?php
use App\Enums\{TicketPriority, TicketStatus};
$pr = TicketPriority::from($ticket['priority']);
$cls = 'w-full border rounded px-3 py-2 text-sm';
$btn = 'px-4 py-2 rounded text-white text-sm disabled:opacity-50';
?>
<div class="mb-4"><a href="<?= url('/tickets') ?>" class="text-blue-700 text-sm hover:underline">&larr; กลับรายการ</a></div>

<div class="grid lg:grid-cols-3 gap-6">
  <!-- ซ้าย: รายละเอียด + Comments -->
  <div class="lg:col-span-2 space-y-6">
    <section class="bg-white rounded shadow p-6">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <h1 class="text-xl font-bold text-slate-800">#<?= (int) $ticket['id'] ?> <?= e($ticket['title']) ?></h1>
        <div class="flex gap-2">
          <span class="px-2 py-1 rounded text-xs <?= $pr->badgeClass() ?>"><?= e($pr->label()) ?></span>
          <span id="status-badge" class="px-2 py-1 rounded text-xs <?= $status->badgeClass() ?>"><?= e($status->label()) ?></span>
        </div>
      </div>
      <p class="mt-4 whitespace-pre-line text-slate-700"><?= e($ticket['description']) ?></p>
      <dl class="mt-4 grid sm:grid-cols-2 gap-2 text-sm text-slate-600">
        <div>ผู้แจ้ง: <b><?= e($ticket['owner_name']) ?></b></div>
        <div>หมวดหมู่: <b><?= e($ticket['category_name']) ?></b></div>
        <div>ช่างผู้รับผิดชอบ: <b><?= e($ticket['technician_name'] ?? 'ยังไม่มอบหมาย') ?></b></div>
        <div>สร้างเมื่อ: <b><?= format_dt($ticket['created_at']) ?></b></div>
        <?php if ($ticket['resolved_at']): ?><div>แก้ไขเสร็จ: <b><?= format_dt($ticket['resolved_at']) ?></b></div><?php endif; ?>
        <?php if ($ticket['closed_at']): ?><div>ปิดงาน: <b><?= format_dt($ticket['closed_at']) ?></b></div><?php endif; ?>
      </dl>
      <?php if ($rating): ?>
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm">
          ⭐ คะแนนความพึงพอใจ: <b><?= (int) $rating['score'] ?>/5</b>
          <?php if ($rating['feedback']): ?> — <?= e($rating['feedback']) ?><?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="bg-white rounded shadow p-6">
      <h2 class="font-bold text-slate-800 mb-3">ความคิดเห็น</h2>
      <div id="comment-list" class="space-y-3">
        <?php foreach ($comments as $c): ?>
          <div class="border rounded p-3 text-sm">
            <div class="flex justify-between text-slate-500 text-xs mb-1">
              <span><b class="text-slate-700"><?= e($c['author_name']) ?></b> (<?= e($c['author_role']) ?>)</span>
              <span><?= format_dt($c['created_at']) ?></span>
            </div>
            <p class="whitespace-pre-line"><?= e($c['body']) ?></p>
            <?php if ($c['image_path']): ?>
              <a href="<?= url('/uploads/' . $c['image_path']) ?>" target="_blank">
                <img src="<?= url('/uploads/' . $c['image_path']) ?>" class="mt-2 max-h-48 rounded border" alt="แนบรูป"></a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($actions['comment']): ?>
      <form method="post" action="<?= url('/api/tickets/' . $ticket['id'] . '/comments') ?>" enctype="multipart/form-data"
            data-ajax data-action="comment" class="mt-4 space-y-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div data-errors class="hidden p-2 bg-red-50 border border-red-300 text-red-800 text-sm rounded"></div>
        <textarea name="body" rows="3" required placeholder="พิมพ์ความคิดเห็น..." class="<?= $cls ?>"></textarea>
        <div class="flex items-center justify-between">
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-xs">
          <button class="<?= $btn ?> bg-blue-600 hover:bg-blue-700">ส่งความคิดเห็น</button>
        </div>
      </form>
      <?php endif; ?>
    </section>
  </div>

  <!-- ขวา: Action Panel + Timeline -->
  <aside class="space-y-6">
    <?php
      $statusUrl = url('/api/tickets/' . $ticket['id'] . '/status');
      $form = fn(string $to) => 'method="post" action="' . $statusUrl . '" enctype="multipart/form-data" data-ajax data-action="status"';
    ?>

    <?php if ($actions['assign']): ?>
    <section class="bg-white rounded shadow p-5">
      <h3 class="font-bold mb-2">มอบหมายช่าง</h3>
      <form <?= $form('assigned') ?> class="space-y-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="status" value="assigned">
        <div data-errors class="hidden p-2 bg-red-50 text-red-800 text-sm rounded"></div>
        <select name="technician_id" required class="<?= $cls ?>">
          <option value="">-- เลือกช่าง --</option>
          <?php foreach ($technicians as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
        </select>
        <button class="<?= $btn ?> bg-indigo-600 hover:bg-indigo-700">มอบหมายงาน</button>
      </form>
    </section>
    <?php endif; ?>

    <?php if ($actions['accept']): ?>
    <section class="bg-white rounded shadow p-5">
      <h3 class="font-bold mb-2">รับงานและเริ่มดำเนินการ</h3>
      <form <?= $form('in_progress') ?> class="space-y-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="status" value="in_progress">
        <div data-errors class="hidden p-2 bg-red-50 text-red-800 text-sm rounded"></div>
        <textarea name="comment" rows="3" required placeholder="ผลการตรวจสอบเบื้องต้น / แผนการซ่อม (บังคับ)" class="<?= $cls ?>"></textarea>
        <button class="<?= $btn ?> bg-amber-600 hover:bg-amber-700">เริ่มดำเนินการ</button>
      </form>
    </section>
    <?php endif; ?>

    <?php if ($actions['resolve']): ?>
    <section class="bg-white rounded shadow p-5">
      <h3 class="font-bold mb-2">แจ้งซ่อมเสร็จ</h3>
      <form <?= $form('resolved') ?> class="space-y-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="status" value="resolved">
        <div data-errors class="hidden p-2 bg-red-50 text-red-800 text-sm rounded"></div>
        <textarea name="comment" rows="2" placeholder="สรุปผลการซ่อม (ไม่บังคับ)" class="<?= $cls ?>"></textarea>
        <label class="block text-xs text-slate-600">แนบรูปผลการซ่อม * (บังคับ)</label>
        <input type="file" name="image" required accept="image/jpeg,image/png,image/webp" class="text-xs">
        <button class="<?= $btn ?> bg-emerald-600 hover:bg-emerald-700">ยืนยันซ่อมเสร็จ</button>
      </form>
    </section>
    <?php endif; ?>

    <?php if ($actions['close']): ?>
    <section class="bg-white rounded shadow p-5">
      <h3 class="font-bold mb-2">ยืนยันปิดงานและให้คะแนน</h3>
      <form <?= $form('closed') ?> class="space-y-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="status" value="closed">
        <div data-errors class="hidden p-2 bg-red-50 text-red-800 text-sm rounded"></div>
        <select name="score" required class="<?= $cls ?>">
          <option value="">-- ให้คะแนน --</option>
          <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= str_repeat('⭐', $i) ?> (<?= $i ?>)</option><?php endfor; ?>
        </select>
        <textarea name="feedback" rows="2" placeholder="ข้อเสนอแนะ (ไม่บังคับ)" class="<?= $cls ?>"></textarea>
        <button class="<?= $btn ?> bg-green-600 hover:bg-green-700">ยืนยันและปิดงาน</button>
      </form>
    </section>
    <?php endif; ?>

    <?php if ($actions['reject']): ?>
    <section class="bg-white rounded shadow p-5 border border-red-200">
      <h3 class="font-bold mb-2 text-red-700">ไม่ผ่าน — ส่งกลับให้ช่างแก้ไข</h3>
      <form <?= $form('in_progress') ?> class="space-y-2">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="status" value="in_progress">
        <div data-errors class="hidden p-2 bg-red-50 text-red-800 text-sm rounded"></div>
        <textarea name="comment" rows="2" required minlength="5" placeholder="ระบุเหตุผล (บังคับ)" class="<?= $cls ?>"></textarea>
        <button class="<?= $btn ?> bg-red-600 hover:bg-red-700">ส่งกลับแก้ไข</button>
      </form>
    </section>
    <?php endif; ?>

    <section class="bg-white rounded shadow p-5">
      <h3 class="font-bold mb-3">ประวัติสถานะ (Audit Log)</h3>
      <ol class="relative border-l border-slate-300 ml-2 space-y-3 text-sm">
        <?php foreach ($logs as $l):
          $to = TicketStatus::from($l['to_status']); ?>
          <li class="ml-4">
            <span class="absolute -left-1.5 w-3 h-3 bg-blue-500 rounded-full"></span>
            <div><span class="px-2 py-0.5 rounded text-xs <?= $to->badgeClass() ?>"><?= e($to->label()) ?></span></div>
            <div class="text-slate-600"><?= e($l['note'] ?? '') ?></div>
            <div class="text-xs text-slate-400"><?= e($l['actor_name']) ?> · <?= format_dt($l['created_at']) ?></div>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  </aside>
</div>
