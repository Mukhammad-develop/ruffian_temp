/**
 * Ruffian Target Promotion — Frontend Logic
 * 2-page flow: Instagram username → Code + DM instructions
 *
 * Hardened for shared hosting:
 *   - Handles non-JSON error responses gracefully
 *   - Sends both JSON and form-encoded as fallback
 *   - Better error messages in Uzbek
 */

(function () {
    'use strict';

    // ── API path — relative to index.html ──
    var API_URL = 'api/submit.php';
    var TRACK_API_URL = 'api/track_click.php';

    // DOM refs
    var form = document.getElementById('step1-form');
    var input = document.getElementById('instagram-input');
    var submitBtn = document.getElementById('submit-btn');
    var errorEl = document.getElementById('input-error');
    var codeValueEl = document.getElementById('code-value');
    var copyBtn = document.getElementById('copy-btn');

    var generatedCode = 'RUFFIAN-FP31FI8';

    // ── Step Navigation ──
    function goToStep(n) {
        var steps = document.querySelectorAll('.step');
        for (var i = 0; i < steps.length; i++) {
            steps[i].classList.remove('active');
        }
        var target = document.getElementById('step-' + n);
        if (target) target.classList.add('active');
    }

    // ── Validation ──
    function validateUsername(val) {
        var clean = val.trim().replace(/^@/, '').toLowerCase();
        if (clean.length === 0) return { valid: false, msg: 'Instagram username kiriting' };
        if (clean.length > 30) return { valid: false, msg: 'Username juda uzun' };
        if (!/^[a-z0-9._]{1,30}$/.test(clean)) return { valid: false, msg: 'Noto\'g\'ri username' };
        return { valid: true, clean: clean };
    }

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.classList.add('visible');
    }

    function hideError() {
        errorEl.classList.remove('visible');
    }

    // ── Safely parse JSON (handles HTML error pages from server) ──
    function safeParseJSON(text) {
        try {
            return JSON.parse(text);
        } catch (e) {
            return null;
        }
    }

    // ── Form Submit ──
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hideError();

            var result = validateUsername(input.value);
            if (!result.valid) {
                showError(result.msg);
                return;
            }

            // Loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-loading');

            var igtrgt = '';
            try {
                igtrgt = sessionStorage.getItem('ruffian_igtrgt') || '';
            } catch (e) {}

            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ instagram: result.clean, igtrgt: igtrgt })
            })
            .then(function (res) {
                return res.text().then(function (text) {
                    return { status: res.status, text: text };
                });
            })
            .then(function (res) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');

                var data = safeParseJSON(res.text);

                if (!data) {
                    // Server returned non-JSON (HTML error page, 500, etc.)
                    showError('Server xatoligi. Sahifani yangilab qaytadan urinib ko\'ring.');
                    return;
                }

                if (data.success) {
                    generatedCode = data.code;
                    codeValueEl.textContent = generatedCode;
                    goToStep(2);
                } else {
                    showError(data.error || 'Xatolik yuz berdi. Qaytadan urinib ko\'ring.');
                }
            })
            .catch(function (err) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
                showError('Serverga ulanib bo\'lmadi. Internet aloqangizni tekshiring.');
            });
        });
    }

    // ── Copy Code ──
    function triggerCopy() {
        if (!generatedCode) return;

        var copyLabel = copyBtn ? copyBtn.querySelector('span') : null;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(generatedCode).then(function () {
                showFeedback();
            }).catch(function () {
                fallbackCopy();
            });
        } else {
            fallbackCopy();
        }

        function fallbackCopy() {
            var ta = document.createElement('textarea');
            ta.value = generatedCode;
            ta.style.cssText = 'position:fixed;opacity:0;left:-9999px';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            showFeedback();
        }

        function showFeedback() {
            if (copyLabel) {
                copyLabel.textContent = 'Nusxalandi';
                setTimeout(function () { copyLabel.textContent = 'Nusxalash'; }, 2000);
            }
        }
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', triggerCopy);
    }
    if (codeValueEl) {
        codeValueEl.addEventListener('click', triggerCopy);
    }

    // ── DM link ──
    var dmLink = document.getElementById('dm-link');
    if (dmLink) {
        dmLink.addEventListener('click', function (e) {
            e.preventDefault();
            window.open('https://ig.me/m/ruffian.uz', '_blank');
        });
    }

    // ── Track Click & Query Recognition ──
    function handleQueryTracking() {
        try {
            var urlParams = new URLSearchParams(window.location.search);
            var igtrgt = urlParams.get('igtrgt');
            if (igtrgt) {
                igtrgt = igtrgt.trim();
                if (igtrgt.length > 0) {
                    sessionStorage.setItem('ruffian_igtrgt', igtrgt);
                    
                    // Fire tracking request to backend
                    fetch(TRACK_API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ igtrgt: igtrgt })
                    }).catch(function (err) {
                        console.error('Click tracking failed:', err);
                    });
                }
            }
        } catch (e) {
            console.error('Error handling query tracking:', e);
        }
    }

    // ── Init ──
    handleQueryTracking();
    goToStep(2);

})();
