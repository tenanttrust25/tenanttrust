// Tenant Trust — push notification client
// Loaded in both panels via render hook. Config in window.TTPushConfig.
(function () {
  'use strict';

  var cfg = window.TTPushConfig || {};
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return;

  var SW_PATH = cfg.swPath || '/tenant/sw.js';
  var SUBSCRIBE_URL = cfg.subscribeUrl || '/push/subscribe';
  var DISMISS_KEY = 'tt-push-dismissed';

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content');
    var cookie = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return cookie ? decodeURIComponent(cookie[1]) : null;
  }

  function encodeBase64Url(buffer) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(buffer)))
      .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  function sendSubscription(subscription) {
    var p256dh = subscription.getKey('p256dh');
    var auth = subscription.getKey('auth');
    if (!p256dh || !auth) return Promise.resolve(false);
    var csrf = getCsrfToken();
    if (!csrf) return Promise.resolve(false);

    return fetch(SUBSCRIBE_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        endpoint: subscription.endpoint,
        keys: {
          p256dh: encodeBase64Url(p256dh),
          auth: encodeBase64Url(auth),
        },
      }),
    }).then(function (r) { return r.ok; }).catch(function () { return false; });
  }

  function isIOS() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
  }

  function isStandalone() {
    return window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
  }

  function showPrompt(registration) {
    if (localStorage.getItem(DISMISS_KEY)) return;

    var banner = document.createElement('div');
    banner.setAttribute('style', 'position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:12px;padding:14px 16px;box-shadow:0 10px 30px rgba(0,0,0,.45);display:flex;align-items:center;gap:12px;font-family:inherit;max-width:420px;margin:0 auto;');
    banner.innerHTML = '<div style="flex:1;font-size:13px;line-height:1.45">🔔 <strong>Get notified</strong><br>Payment reminders, overdue alerts &amp; maintenance updates.</div>' +
      '<button type="button" style="background:#f59e0b;color:#0f172a;border:0;border-radius:8px;padding:8px 14px;font-weight:700;cursor:pointer;white-space:nowrap">Enable</button>' +
      '<button type="button" aria-label="Dismiss" style="background:transparent;color:#94a3b8;border:0;cursor:pointer;font-size:18px;padding:4px">✕</button>';
    document.body.appendChild(banner);

    var buttons = banner.querySelectorAll('button');
    var enableBtn = buttons[0];
    var dismissBtn = buttons[1];

    dismissBtn.addEventListener('click', function () {
      localStorage.setItem(DISMISS_KEY, '1');
      banner.remove();
    });

    enableBtn.addEventListener('click', function () {
      Notification.requestPermission().then(function (permission) {
        if (permission !== 'granted') {
          localStorage.setItem(DISMISS_KEY, '1');
          banner.remove();
          return;
        }
        registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(cfg.vapidPublicKey),
        }).then(sendSubscription).then(function () {
          banner.remove();
        }).catch(function () { banner.remove(); });
      });
    });
  }

  navigator.serviceWorker.register(SW_PATH).then(function (registration) {
    if (!registration.pushManager) return;

    // iOS only supports push after the app is installed to the home screen.
    // If not standalone, skip the prompt — the install banner handles iOS onboarding.
    if (isIOS() && !isStandalone()) return;

    if (Notification.permission === 'granted') {
      registration.pushManager.getSubscription().then(function (sub) {
        if (sub) {
          sendSubscription(sub); // re-sync on every load
        } else if (cfg.authenticated) {
          registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(cfg.vapidPublicKey),
          }).then(sendSubscription).catch(function () { /* no gesture — fine */ });
        }
      });
      return;
    }

    if (Notification.permission === 'default' && cfg.authenticated) {
      showPrompt(registration);
    }
  }).catch(function () {});
})();
