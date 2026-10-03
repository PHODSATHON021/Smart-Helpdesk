<form method="post" action="<?= url('/login') ?>" class="bg-white rounded-xl shadow-xl p-8 space-y-4">
  <h1 class="text-2xl font-bold text-blue-800 text-center">🛠️ Smart IT Helpdesk</h1>
  <p class="text-center text-slate-500 text-sm">เข้าสู่ระบบเพื่อแจ้งซ่อมและติดตามงาน</p>
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div>
    <label class="block text-sm mb-1">อีเมล</label>
    <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none">
  </div>
  <div>
    <label class="block text-sm mb-1">รหัสผ่าน</label>
    <input type="password" name="password" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none">
  </div>
  <button class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold">เข้าสู่ระบบ</button>
  <div class="text-xs text-slate-500 border-t pt-3">
    บัญชีทดลอง (รหัสผ่านหลังรัน seed): <br>
    admin@example.com / admin1234 · tech1@example.com / tech1234 · user@example.com / user1234
  </div>
</form>
