// Tenant Trust — install prompt
// Shows a small banner telling users to install the app to their home screen.
// Config in window.TTInstallConfig (set by the panel providers).
(function () {
  'use strict';

  var cfg = window.TTInstallConfig || {};
  var DISMISS_KEY = 'tt-install-dismissed';
  var INSTALLED_KEY = 'tt-install-installed';
  var deferredPrompt = null;

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true;
  }

  function isIOS() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent) ||
      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  }

  function removeBanner() {
    var b = document.getElementById('tt-install-banner');
    if (b) b.remove();
  }

  function showBanner(html) {
    if (document.getElementById('tt-install-banner')) return;

    var banner = document.createElement('div');
    banner.id = 'tt-install-banner';
    banner.setAttribute('style', 'position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:12px;padding:14px 16px;box-shadow:0 10px 30px rgba(0,0,0,.45);display:flex;align-items:center;gap:12px;font-family:inherit;max-width:420px;margin:0 auto;');
    banner.innerHTML = html;
    document.body.appendChild(banner);

    var dismiss = banner.querySelector('.tt-install-dismiss');
    if (dismiss) dismiss.addEventListener('click', function () {
      localStorage.setItem(DISMISS_KEY, '1');
      removeBanner();
    });

    var install = banner.querySelector('.tt-install-install');
    if (install) install.addEventListener('click', function () {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function (choice) {
        if (choice.outcome === 'accepted') {
          localStorage.setItem(INSTALLED_KEY, '1');
          removeBanner();
        }
        deferredPrompt = null;
      });
    });
  }

  function maybeShow() {
    if (isStandalone()) return;
    if (localStorage.getItem(DISMISS_KEY)) return;
    if (localStorage.getItem(INSTALLED_KEY)) return;
    if (typeof cfg.authenticated !== 'undefined' && !cfg.authenticated) return;

    if (isIOS()) {
      // iOS Safari has no beforeinstallprompt — show instructions instead
      showBanner(
        '<div style="flex:1;font-size:13px;line-height:1.45">📲 <strong>Install this app</strong><br>Tap the <strong>Share</strong> button, then <strong>Add to Home Screen</strong> for one-tap access.</div>' +
        '<button type="button" class="tt-install-dismiss" aria-label="Dismiss" style="background:transparent;color:#94a3b8;border:0;cursor:pointer;font-size:18px;padding:4px">✕</button>'
      );
      return;
    }

    // Android/Chrome: only show when the browser offers an install prompt
    if (deferredPrompt) {
      showBanner(
        '<div style="flex:1;font-size:13px;line-height:1.45">📲 <strong>Install this app</strong><br>Add it to your home screen for one-tap access.</div>' +
        '<button type="button" class="tt-install-install" style="background:#f59e0b;color:#0f172a;border:0;border-radius:8px;padding:8px 14px;font-weight:700;cursor:pointer;white-space:nowrap">Install</button>' +
        '<button type="button" class="tt-install-dismiss" aria-label="Dismiss" style="background:transparent;color:#94a3b8;border:0;cursor:pointer;font-size:18px;padding:4px">✕</button>'
      );
    }
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    setTimeout(maybeShow, 4000);
  });

  window.addEventListener('appinstalled', function () {
    localStorage.setItem(INSTALLED_KEY, '1');
    removeBanner();
  });

  // iOS doesn't fire beforeinstallprompt — check shortly after load
  if (isIOS()) {
    setTimeout(maybeShow, 4000);
  }

  // Re-check when returning to the tab (user may have installed elsewhere)
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') maybeShow();
  });
})();
