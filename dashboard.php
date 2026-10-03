<?php use App\Enums\TicketStatus; $max = max(1, ...array_values($stats['byStatus'])); ?>
<h1 class="text-2xl font-bold text-blue-800 mb-4">Dashboard & Reports</h1>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded shadow p-5"><div class="text-sm text-slate-500">Ticket ทั้งหมด</div><div class="text-3xl font-bold text-blue-700"><?= (int) $stats['total'] ?></div></div>
  <div class="bg-white rounded shadow p-5"><div class="text-sm text-slate-500">เวลาแก้ไขเฉลี่ย (ชม.)</div><div class="text-3xl font-bold text-amber-600"><?= e($stats['avgResolutionHours']) ?></div></div>
  <div class="bg-white rounded shadow p-5"><div class="text-sm text-slate-500">คะแนนเฉลี่ย</div><div class="text-3xl font-bold text-yellow-500">⭐ <?= e($stats['avgRating'] ?: '-') ?></div></div>
  <div class="bg-white rounded shadow p-5"><div class="text-sm text-slate-500">ปิดงานแล้ว</div><div class="text-3xl font-bold text-green-600"><?= (int) $stats['byStatus']['closed'] ?></div></div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
  <section class="bg-white rounded shadow p-6">
    <h2 class="font-bold mb-4">จำนวน Ticket ตามสถานะ</h2>
    <?php foreach (TicketStatus::cases() as $s): $n = $stats['byStatus'][$s->value]; ?>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1"><span><?= e($s->label()) ?></span><b><?= $n ?></b></div>
        <div class="h-3 bg-slate-100 rounded"><div class="h-3 rounded bg-blue-500" style="width: <?= round($n / $max * 100) ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="bg-white rounded shadow p-6">
    <h2 class="font-bold mb-4">ช่างที่ปิดงานได้มากที่สุด</h2>
    <table class="w-full text-sm">
      <thead><tr class="text-left text-slate-500"><th class="py-1">ช่าง</th><th>งานที่เสร็จ</th><th>คะแนนเฉลี่ย</th></tr></thead>
      <tbody>
      <?php foreach ($stats['topTechnicians'] as $t): ?>
        <tr class="border-t"><td class="py-2"><?= e($t['name']) ?></td><td><?= (int) $t['total'] ?></td><td><?= e($t['avg_score'] ?? '-') ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$stats['topTechnicians']): ?><tr><td colspan="3" class="py-4 text-center text-slate-400">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
      </tbody>
    </table>

    <h2 class="font-bold mt-6 mb-3">คะแนนความพึงพอใจรายเดือน</h2>
    <table class="w-full text-sm">
      <thead><tr class="text-left text-slate-500"><th class="py-1">เดือน</th><th>คะแนนเฉลี่ย</th><th>จำนวนรีวิว</th></tr></thead>
      <tbody>
      <?php foreach ($stats['monthlyRatings'] as $m): ?>
        <tr class="border-t"><td class="py-2"><?= e($m['month']) ?></td><td>⭐ <?= e($m['avg_score']) ?></td><td><?= (int) $m['total'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$stats['monthlyRatings']): ?><tr><td colspan="3" class="py-4 text-center text-slate-400">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>
</div>
