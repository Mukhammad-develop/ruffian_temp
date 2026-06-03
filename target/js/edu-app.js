/**
 * Ruffian Edu Flow — Frontend Logic
 * Educational landing page: tracks page visits + CTA clicks, no promo code.
 *
 * Tracking:
 *  - Page visit → POST api/track_edu_visit.php  (once per session tab)
 *  - CTA click  → POST api/track_edu_cta.php
 */

(function () {
    'use strict';

    var VISIT_API = 'api/track_edu_visit.php';
    var CTA_API   = 'api/track_edu_cta.php';

    // ── Resolve igtrgt: URL param → sessionStorage → 'organic' ──
    function resolveIgtrgt() {
        try {
            var urlParams = new URLSearchParams(window.location.search);
            var param = urlParams.get('igtrgt');
            if (param && param.trim().length > 0) {
                sessionStorage.setItem('ruffian_edu_igtrgt', param.trim());
            }
            return sessionStorage.getItem('ruffian_edu_igtrgt') || 'organic';
        } catch (e) {
            return 'organic';
        }
    }

    // ── Fire a tracking request ──
    function track(url, igtrgt) {
        try {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ igtrgt: igtrgt })
            }).catch(function (err) {
                console.error('Tracking failed:', url, err);
            });
        } catch (e) {}
    }

    // ── Track Visit (once per session tab) ──
    function trackVisit() {
        try {
            if (sessionStorage.getItem('ruffian_edu_visit_tracked') === 'true') return;
            var igtrgt = resolveIgtrgt();
            track(VISIT_API, igtrgt);
            sessionStorage.setItem('ruffian_edu_visit_tracked', 'true');
        } catch (e) {}
    }

    // ── Track CTA click ──
    var ctaBtn = document.getElementById('edu-dm-link');
    if (ctaBtn) {
        ctaBtn.addEventListener('click', function () {
            var igtrgt = resolveIgtrgt();
            track(CTA_API, igtrgt);
        });
    }

    // ── Init ──
    trackVisit();

})();
