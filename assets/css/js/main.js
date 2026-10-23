/* ============================================================
   GetKey – main.js
   - Custom select dropdown
   - HWID fingerprint (canvas + userAgent + screen)
   ============================================================ */

// ── HWID fingerprint ─────────────────────────────────────────
async function getHWID() {
  try {
    const canvas  = document.createElement('canvas');
    const ctx     = canvas.getContext('2d');
    ctx.textBaseline = 'top';
    ctx.font      = '14px Arial';
    ctx.fillStyle = '#f60';
    ctx.fillRect(125, 1, 62, 20);
    ctx.fillStyle = '#069';
    ctx.fillText('GetKey🔑', 2, 15);
    const fp = [
      canvas.toDataURL(),
      navigator.userAgent,
      screen.width + 'x' + screen.height,
      Intl.DateTimeFormat().resolvedOptions().timeZone,
      navigator.language,
      navigator.hardwareConcurrency || 0,
    ].join('|');

    // SHA-256 hash via SubtleCrypto
    const buf  = new TextEncoder().encode(fp);
    const hash = await crypto.subtle.digest('SHA-256', buf);
    return Array.from(new Uint8Array(hash))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
  } catch {
    // Fallback random stored in localStorage
    let h = localStorage.getItem('_gk_hwid');
    if (!h) { h = Math.random().toString(36).slice(2).repeat(4).slice(0, 40); localStorage.setItem('_gk_hwid', h); }
    return h;
  }
}

// ── Custom Select ─────────────────────────────────────────────
const wrapper  = document.getElementById('selectWrapper');
const display  = document.getElementById('selectDisplay');
const hiddenId = document.getElementById('appIdInput');
const items    = document.querySelectorAll('.dropdown-item');

if (wrapper) {
  // Đánh dấu item đầu tiên là selected
  if (items.length) items[0].classList.add('selected');

  display.addEventListener('click', () => wrapper.classList.toggle('open'));

  items.forEach(item => {
    item.addEventListener('click', () => {
      items.forEach(i => i.classList.remove('selected'));
      item.classList.add('selected');
      display.childNodes[0].textContent = item.textContent.trim();
      hiddenId.value = item.dataset.id;
      wrapper.classList.remove('open');
    });
  });

  document.addEventListener('click', e => {
    if (!wrapper.contains(e.target)) wrapper.classList.remove('open');
  });
}

// ── Inject HWID before submit ──────────────────────────────────
const form      = document.getElementById('getkeyForm');
const hwidInput = document.getElementById('hwidInput');
const submitBtn = document.getElementById('submitBtn');

if (form) {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang xử lý…';

    hwidInput.value = await getHWID();
    form.submit();
  });
}
