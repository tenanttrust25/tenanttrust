/* Tenant Trust — lead capture form injector for /tools/ pages. */
(function () {
  "use strict";
  if (window.__ttLeadLoaded) return;
  window.__ttLeadLoaded = true;

  var seg = location.pathname.replace(/\/+$/, "").split("/").pop() || "tools";
  if (seg === "tools" || seg === "") seg = "tools-index";

  var css =
    ".tt-lead{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-top:14px;color:#0f172a;" +
    "font:15px/1.5 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif}" +
    ".tt-lead .tt-lbl{font-weight:700;font-size:.95rem;margin-bottom:3px}" +
    ".tt-lead .tt-sub{color:#64748b;font-size:.8rem;margin:0 0 10px}" +
    ".tt-lead .tt-row{display:flex;gap:8px;flex-wrap:wrap}" +
    ".tt-lead input[type=email]{flex:1 1 200px;min-width:0;padding:11px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:1rem}" +
    ".tt-lead input[type=email]:focus{outline:2px solid #0f766e;border-color:#0f766e}" +
    ".tt-lead button{padding:11px 16px;border:0;border-radius:10px;background:#0f766e;color:#fff;font-weight:700;cursor:pointer}" +
    ".tt-lead button:disabled{opacity:.55;cursor:default}" +
    ".tt-lead .tt-hp{position:absolute;left:-9999px;width:1px;height:1px;opacity:0}" +
    ".tt-lead .tt-msg{font-size:.8rem;color:#64748b;margin:8px 0 0}" +
    ".tt-lead .tt-err{color:#b91c1c}" +
    ".tt-lead .tt-ok{color:#15803d;font-weight:600;margin:0}";

  var st = document.createElement("style");
  st.textContent = css;
  document.head.appendChild(st);

  var form = document.createElement("form");
  form.className = "tt-lead";
  form.setAttribute("novalidate", "novalidate");
  form.innerHTML =
    '<div class="tt-lbl">Get a free renewal reminder</div>' +
    '<p class="tt-sub">We\'ll email you before the next rent-increase window, plus occasional Ontario landlord tips. No spam, unsubscribe anytime.</p>' +
    '<div class="tt-row">' +
    '<input type="email" name="email" required placeholder="you@email.com" autocomplete="email" aria-label="Email address">' +
    '<button type="submit">Notify me</button>' +
    "</div>" +
    '<input type="text" name="hp" class="tt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">' +
    '<p class="tt-msg" role="status"></p>';

  var host = document.querySelector(".promo") || document.querySelector(".lead-slot");
  if (host) {
    host.appendChild(form);
  } else {
    var anchor = document.querySelector("footer") || document.querySelector(".wrap");
    if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(form, anchor);
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var btn = form.querySelector("button");
    var msg = form.querySelector(".tt-msg");
    var email = (form.querySelector('input[name=email]') || {}).value || "";
    var hp = (form.querySelector('input[name=hp]') || {}).value || "";
    if (!email || email.indexOf("@") < 1) {
      if (msg) { msg.textContent = "Please enter a valid email address."; msg.className = "tt-msg tt-err"; }
      return;
    }
    btn.disabled = true;
    if (msg) { msg.textContent = "Sending…"; msg.className = "tt-msg"; }
    fetch("/tools/subscribe.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email, tool: seg, hp: hp }),
    })
      .then(function (r) { return r.json().catch(function () { return { ok: r.ok }; }); })
      .then(function (j) {
        if (j && j.ok) {
          form.innerHTML = '<p class="tt-ok">✓ Thanks — you\'re on the list. We\'ll be in touch before the next increase window.</p>';
        } else {
          btn.disabled = false;
          if (msg) { msg.textContent = "Please enter a valid email address."; msg.className = "tt-msg tt-err"; }
        }
      })
      .catch(function () {
        btn.disabled = false;
        if (msg) { msg.textContent = "Something went wrong — please try again."; msg.className = "tt-msg tt-err"; }
      });
  });
})();
