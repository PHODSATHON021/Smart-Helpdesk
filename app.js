/**
 * Vanilla JS + Fetch API (ตาม Architecture: Client Layer)
 * ฟอร์มที่มี data-ajax จะถูกส่งด้วย fetch แล้วจัดการผลลัพธ์ตาม data-action
 *   create  → redirect ไปหน้า Ticket ที่สร้าง   (Sequence 6.1)
 *   comment → appendComment โดยไม่รีโหลดหน้า    (Sequence 6.2)
 *   status  → updateBadge แล้วรีโหลดเพื่อสลับ Action Panel
 */
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

function showToast(message, ok = true) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.className = 'mb-4 p-3 rounded border text-sm ' +
    (ok ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800');
  t.textContent = message;
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s ?? '';
  return d.innerHTML;
}

function appendComment(c) {
  const list = document.getElementById('comment-list');
  if (!list) return;
  const img = c.image
    ? `<a href="${escapeHtml(c.image)}" target="_blank"><img src="${escapeHtml(c.image)}" class="mt-2 max-h-48 rounded border" alt="แนบรูป"></a>`
    : '';
  list.insertAdjacentHTML('beforeend', `
    <div class="border rounded p-3 text-sm bg-blue-50">
      <div class="flex justify-between text-slate-500 text-xs mb-1">
        <span><b class="text-slate-700">${escapeHtml(c.author)}</b> (${escapeHtml(c.role)})</span>
        <span>${escapeHtml(c.time)}</span>
      </div>
      <p class="whitespace-pre-line">${escapeHtml(c.body)}</p>${img}
    </div>`);
}

function updateBadge(ticket) {
  const badge = document.getElementById('status-badge');
  if (!badge) return;
  badge.className = 'px-2 py-1 rounded text-xs ' + ticket.badge;
  badge.textContent = ticket.label;
}

function showErrors(form, data) {
  const box = form.querySelector('[data-errors]');
  const msgs = data?.errors && Object.keys(data.errors).length
    ? Object.values(data.errors).flat()
    : [data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'];
  if (box) {
    box.innerHTML = msgs.map(m => `<div>• ${escapeHtml(m)}</div>`).join('');
    box.classList.remove('hidden');
  } else {
    showToast(msgs.join(' / '), false);
  }
}

document.addEventListener('submit', async (e) => {
  const form = e.target.closest('form[data-ajax]');
  if (!form) return;
  e.preventDefault();

  const btn = form.querySelector('button:not([type="button"])');
  const box = form.querySelector('[data-errors]');
  if (box) box.classList.add('hidden');
  if (btn) btn.disabled = true;

  try {
    const res = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf },
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({ ok: false, message: 'เซิร์ฟเวอร์ตอบกลับผิดรูปแบบ' }));
    if (!res.ok || !data.ok) throw data;

    switch (form.dataset.action) {
      case 'create':
        window.location.href = data.redirect;
        return;
      case 'comment':
        appendComment(data.comment);
        form.reset();
        break;
      case 'status':
        updateBadge(data.ticket);
        showToast(data.message, true);
        setTimeout(() => window.location.reload(), 600);
        return;
    }
  } catch (err) {
    showErrors(form, err);
  } finally {
    if (btn) btn.disabled = false;
  }
});
