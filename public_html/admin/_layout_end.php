        </main>
    </div>
</div>

<script>
window.csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
window.apiPost = async function(url, data) {
    data = data || {};
    var fd = new FormData();
    fd.append('csrf_token', window.csrfToken);
    Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
    try {
        var r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        return await r.json();
    } catch(e) { return { ok: false, xato: 'Tarmoq xatosi' }; }
};
</script>
</body>
</html>
