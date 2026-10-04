<aside id="vf-cookie-notice" hidden role="region" aria-label="Aviso de cookies" style="position:fixed; z-index:9999; left:12px; right:12px; bottom:calc(84px + env(safe-area-inset-bottom, 0px)); margin:0 auto; max-width:760px; padding:14px 16px; border:1px solid #454545; border-radius:14px; background:#171717; color:#f5f5f5; box-shadow:0 12px 40px #0009; font:14px/1.45 system-ui,sans-serif;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <p style="margin:0; flex:1; min-width:220px;">
            Usamos cookies necesarias para mantener la sesión y proteger la cuenta. No usamos cookies de publicidad ni analítica.
            <a href="{{ route('legal.cookies') }}" style="color:#fbbf24; text-decoration:underline;">Ver política de cookies</a>.
        </p>
        <button id="vf-cookie-notice-dismiss" type="button" style="border:0; border-radius:8px; padding:9px 14px; background:#e63946; color:#fff; font-weight:700; cursor:pointer;">Entendido</button>
    </div>
</aside>
<script>
(() => {
    const notice = document.getElementById('vf-cookie-notice');
    const dismiss = document.getElementById('vf-cookie-notice-dismiss');
    if (!notice || !dismiss) return;

    try {
        if (window.sessionStorage.getItem('vf-cookie-notice-seen') === '1') return;
    } catch (_) {}

    notice.hidden = false;
    dismiss.addEventListener('click', () => {
        try { window.sessionStorage.setItem('vf-cookie-notice-seen', '1'); } catch (_) {}
        notice.hidden = true;
    });
})();
</script>
