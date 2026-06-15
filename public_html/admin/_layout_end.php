        </main>
    </div><!-- /.flex-1 -->
</div><!-- /.flex -->

<script>
window.csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
window.apiPost = async function(url, data = {}) {
    const fd = new FormData();
    fd.append('csrf_token', window.csrfToken);
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    try {
        const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        return await r.json();
    } catch(e) { return { ok: false, xato: 'Tarmoq xatosi' }; }
};
</script>
</body>
</html>
