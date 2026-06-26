{{--
    Modal konfirmasi + toast global (menggantikan window.confirm/alert native).

    Cara pakai:
    1) Form: tambahkan atribut pada <form>
         data-confirm="Pesan konfirmasi"        (wajib — memicu modal)
         data-confirm-variant="danger"          (opsional — tombol merah utk aksi destruktif)
         data-confirm-title="Judul"             (opsional)
         data-confirm-ok="Label tombol ya"      (opsional)
    2) Programatik (mis. di handler JS):
         const ok = await window.maxyConfirm({ message, variant, title, okLabel });
    3) Notifikasi ringan:
         window.maxyToast('Pesan', 'danger'|'success'|'info');
--}}

<div id="maxy-confirm-overlay" class="maxy-confirm-overlay" aria-hidden="true">
    <div class="maxy-confirm-card" role="dialog" aria-modal="true" aria-labelledby="maxy-confirm-title" data-variant="primary">
        <div class="maxy-confirm-icon" id="maxy-confirm-icon"></div>
        <h3 id="maxy-confirm-title" class="maxy-confirm-title">Konfirmasi</h3>
        <p id="maxy-confirm-message" class="maxy-confirm-message"></p>
        <div class="maxy-confirm-actions">
            <button type="button" id="maxy-confirm-cancel" class="maxy-confirm-btn maxy-confirm-btn-cancel">Batal</button>
            <button type="button" id="maxy-confirm-ok" class="maxy-confirm-btn maxy-confirm-btn-ok">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<div id="maxy-toast-container" class="maxy-toast-container" aria-live="polite"></div>

<style>
    .maxy-confirm-overlay{
        position:fixed; inset:0; z-index:10000;
        display:flex; align-items:center; justify-content:center; padding:20px;
        background:rgba(15,23,42,.45); backdrop-filter:blur(2px);
        opacity:0; visibility:hidden; transition:opacity .18s ease, visibility .18s ease;
    }
    .maxy-confirm-overlay.show{ opacity:1; visibility:visible; }
    .maxy-confirm-card{
        width:100%; max-width:380px; background:#fff; border-radius:16px;
        padding:24px 22px 18px; box-shadow:0 20px 50px rgba(0,0,0,.25);
        text-align:center; transform:translateY(12px) scale(.97); transition:transform .2s ease;
    }
    .maxy-confirm-overlay.show .maxy-confirm-card{ transform:translateY(0) scale(1); }
    .maxy-confirm-icon{
        width:52px; height:52px; border-radius:50%; margin:0 auto 14px;
        display:flex; align-items:center; justify-content:center;
        background:rgba(29,78,216,.10); color:var(--maxy-navy,#1d4ed8);
    }
    .maxy-confirm-icon svg{ width:26px; height:26px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .maxy-confirm-card[data-variant="danger"] .maxy-confirm-icon{ background:#FEE2E2; color:#DC2626; }
    .maxy-confirm-title{ font-size:17px; font-weight:800; color:var(--fg-1,#0f172a); margin:0 0 6px; }
    .maxy-confirm-message{ font-size:13px; line-height:1.55; color:var(--fg-3,#475569); margin:0 0 20px; white-space:pre-line; }
    .maxy-confirm-actions{ display:flex; gap:10px; }
    .maxy-confirm-btn{
        flex:1; padding:11px 14px; border-radius:10px; font-size:14px; font-weight:700;
        cursor:pointer; border:1px solid transparent; transition:filter .15s ease, background .15s ease;
    }
    .maxy-confirm-btn-cancel{ background:var(--bg-2,#f1f5f9); color:var(--fg-2,#334155); border-color:var(--bg-3,#e2e8f0); }
    .maxy-confirm-btn-cancel:hover{ background:var(--bg-3,#e2e8f0); }
    .maxy-confirm-btn-ok{ background:var(--maxy-navy,#1d4ed8); color:#fff; }
    .maxy-confirm-btn-ok:hover{ filter:brightness(1.08); }
    .maxy-confirm-card[data-variant="danger"] .maxy-confirm-btn-ok{ background:#DC2626; }

    .maxy-toast-container{
        position:fixed; top:18px; left:50%; transform:translateX(-50%);
        z-index:10001; display:flex; flex-direction:column; gap:8px; align-items:center;
        pointer-events:none; width:max-content; max-width:90vw;
    }
    .maxy-toast{
        pointer-events:auto; padding:11px 16px; border-radius:10px; font-size:13px; font-weight:600;
        color:#fff; box-shadow:0 8px 24px rgba(0,0,0,.18); max-width:420px;
        opacity:0; transform:translateY(-10px); transition:opacity .2s ease, transform .2s ease;
    }
    .maxy-toast.show{ opacity:1; transform:translateY(0); }
    .maxy-toast-info{ background:#1d4ed8; }
    .maxy-toast-success{ background:#16A34A; }
    .maxy-toast-danger{ background:#DC2626; }
</style>

<script>
(function () {
    const overlay  = document.getElementById('maxy-confirm-overlay');
    const card     = overlay.querySelector('.maxy-confirm-card');
    const iconEl   = document.getElementById('maxy-confirm-icon');
    const titleEl  = document.getElementById('maxy-confirm-title');
    const msgEl    = document.getElementById('maxy-confirm-message');
    const okBtn    = document.getElementById('maxy-confirm-ok');
    const cancelBtn= document.getElementById('maxy-confirm-cancel');
    let resolver = null;
    let lastFocused = null;

    const ICONS = {
        primary: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
        danger:  '<svg viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    };

    function open(opts) {
        opts = opts || {};
        const variant = opts.variant === 'danger' ? 'danger' : 'primary';
        card.setAttribute('data-variant', variant);
        iconEl.innerHTML = ICONS[variant];
        titleEl.textContent = opts.title || (variant === 'danger' ? 'Konfirmasi Tindakan' : 'Konfirmasi');
        msgEl.textContent   = opts.message || 'Apakah Anda yakin?';
        okBtn.textContent   = opts.okLabel || (variant === 'danger' ? 'Ya, Lanjutkan' : 'Ya, Lanjutkan');

        lastFocused = document.activeElement;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => okBtn.focus(), 60);

        return new Promise(res => { resolver = res; });
    }

    function close(result) {
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocused) { try { lastFocused.focus(); } catch (e) {} }
        if (resolver) { const r = resolver; resolver = null; r(result); }
    }

    okBtn.addEventListener('click', () => close(true));
    cancelBtn.addEventListener('click', () => close(false));
    overlay.addEventListener('click', e => { if (e.target === overlay) close(false); });
    document.addEventListener('keydown', e => {
        if (!overlay.classList.contains('show')) return;
        if (e.key === 'Escape') close(false);
        else if (e.key === 'Enter') { e.preventDefault(); close(true); }
    });

    window.maxyConfirm = open;

    // Intercept semua form ber-atribut data-confirm (capture phase agar dulu dari handler lain).
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.confirmBypass === '1') { form.dataset.confirmBypass = ''; return; }
        const msg = form.getAttribute('data-confirm');
        if (!msg) return;

        e.preventDefault();
        open({
            message: msg,
            variant: form.getAttribute('data-confirm-variant') || 'primary',
            title:   form.getAttribute('data-confirm-title') || undefined,
            okLabel: form.getAttribute('data-confirm-ok') || undefined,
        }).then(ok => {
            if (!ok) return;
            form.dataset.confirmBypass = '1';
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        });
    }, true);

    // Toast ringan (pengganti alert()).
    const tc = document.getElementById('maxy-toast-container');
    window.maxyToast = function (message, type) {
        const el = document.createElement('div');
        el.className = 'maxy-toast maxy-toast-' + (type || 'info');
        el.textContent = message;
        tc.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 250);
        }, 4000);
    };
})();
</script>
