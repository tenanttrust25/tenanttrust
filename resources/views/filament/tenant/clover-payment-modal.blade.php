<div>
    <input type="hidden" value="{{ csrf_token() }}" id="csrf-token-{{ $invoiceId }}">

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
            <div id="card-number-{{ $invoiceId }}" class="border border-gray-300 rounded-lg p-3 min-h-[44px] bg-white"></div>
            <div id="card-number-errors-{{ $invoiceId }}" class="text-xs text-red-500 mt-1"></div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                <div id="card-date-{{ $invoiceId }}" class="border border-gray-300 rounded-lg p-3 min-h-[44px] bg-white"></div>
                <div id="card-date-errors-{{ $invoiceId }}" class="text-xs text-red-500 mt-1"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                <div id="card-cvv-{{ $invoiceId }}" class="border border-gray-300 rounded-lg p-3 min-h-[44px] bg-white"></div>
                <div id="card-cvv-errors-{{ $invoiceId }}" class="text-xs text-red-500 mt-1"></div>
            </div>
        </div>

        <div id="clover-response-{{ $invoiceId }}" style="display:none;" class="p-3 rounded-lg border text-sm"></div>

        <div id="clover-loading-{{ $invoiceId }}" style="display:none;" class="flex items-center justify-center py-3">
            <svg class="animate-spin h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="ml-2 text-sm text-gray-500">Processing payment...</span>
        </div>

        <button type="button" id="pay-btn-{{ $invoiceId }}" onclick="processCloverPayment({{ $invoiceId }})"
                class="w-full inline-flex items-center justify-center px-4 py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold rounded-lg transition-colors text-base">
            Pay ${{ number_format($amount, 2) }}
        </button>
    </div>

    @php
        $publicTokenJs = $publicToken;
        $merchantIdJs = $merchantId;
        $redirectUrlJs = $redirectUrl;
        $invoiceIdJs = $invoiceId;
        $csrfTokenJs = csrf_token();
    @endphp

    <script>
        (function() {
            var invId = {{ $invoiceId }};
            var pubToken = @json($publicToken);
            var merchId = @json($merchantId);
            var redirectUrl = @json($redirectUrl);

            function initForm() {
                if (typeof Clover === 'undefined') {
                    setTimeout(initForm, 300);
                    return;
                }

                var clover = new Clover(pubToken, {
                    merchantId: merchId,
                    locale: 'en-CA'
                });

                var elements = clover.elements();
                var styles = { input: { fontSize: '16px', fontFamily: 'Arial, sans-serif', color: '#1f2937' } };

                var el = {
                    number: elements.create('CARD_NUMBER', styles),
                    date: elements.create('CARD_DATE', styles),
                    cvv: elements.create('CARD_CVV', styles)
                };

                el.number.mount('card-number-' + invId);
                el.number.on('error', function(e) {
                    document.getElementById('card-number-errors-' + invId).textContent = e.error && e.error.message ? e.error.message : '';
                });

                el.date.mount('card-date-' + invId);
                el.date.on('error', function(e) {
                    document.getElementById('card-date-errors-' + invId).textContent = e.error && e.error.message ? e.error.message : '';
                });

                el.cvv.mount('card-cvv-' + invId);
                el.cvv.on('error', function(e) {
                    document.getElementById('card-cvv-errors-' + invId).textContent = e.error && e.error.message ? e.error.message : '';
                });

                window.__clover = window.__clover || {};
                window.__clover[invId] = clover;
            }

            initForm();
        })();
    </script>

    <script>
        function processCloverPayment(invoiceId) {
            var btn = document.getElementById('pay-btn-' + invoiceId);
            var loading = document.getElementById('clover-loading-' + invoiceId);
            var resp = document.getElementById('clover-response-' + invoiceId);

            btn.disabled = true;
            loading.style.display = 'flex';
            resp.style.display = 'none';

            var clover = (window.__clover || {})[invoiceId];
            if (!clover) {
                resp.className = 'p-3 rounded-lg border text-sm text-red-600 bg-red-50 border-red-200';
                resp.innerHTML = '\u274C Payment form not ready. Please close and try again.';
                resp.style.display = 'block';
                btn.disabled = false;
                loading.style.display = 'none';
                return;
            }

            clover.createToken().then(function(tokenResult) {
                if (tokenResult.errors && tokenResult.errors.length > 0) {
                    resp.className = 'p-3 rounded-lg border text-sm text-red-600 bg-red-50 border-red-200';
                    resp.innerHTML = '\u274C ' + tokenResult.errors.map(function(e) { return e.message; }).join(', ');
                    resp.style.display = 'block';
                    btn.disabled = false;
                    loading.style.display = 'none';
                    return;
                }

                var sourceToken = tokenResult.token || tokenResult.id;
                if (!sourceToken) {
                    resp.className = 'p-3 rounded-lg border text-sm text-red-600 bg-red-50 border-red-200';
                    resp.innerHTML = '\u274C Failed to tokenize card. Please try again.';
                    resp.style.display = 'block';
                    btn.disabled = false;
                    loading.style.display = 'none';
                    return;
                }

                var csrf = document.getElementById('csrf-token-' + invoiceId).value;

                fetch('/api/clover/charge', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ source_token: sourceToken, invoice_id: invoiceId })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        resp.className = 'p-3 rounded-lg border text-sm text-green-600 bg-green-50 border-green-200';
                        resp.innerHTML = '\u2705 ' + data.message;
                        resp.style.display = 'block';
                        loading.style.display = 'none';
                        setTimeout(function() { window.location.href = redirectUrl; }, 1500);
                    } else {
                        resp.className = 'p-3 rounded-lg border text-sm text-red-600 bg-red-50 border-red-200';
                        resp.innerHTML = '\u274C ' + data.message;
                        resp.style.display = 'block';
                        btn.disabled = false;
                        loading.style.display = 'none';
                    }
                })
                .catch(function(err) {
                    resp.className = 'p-3 rounded-lg border text-sm text-red-600 bg-red-50 border-red-200';
                    resp.innerHTML = '\u274C Connection error: ' + err.message;
                    resp.style.display = 'block';
                    btn.disabled = false;
                    loading.style.display = 'none';
                });

            }).catch(function(err) {
                resp.className = 'p-3 rounded-lg border text-sm text-red-600 bg-red-50 border-red-200';
                resp.innerHTML = '\u274C Error: ' + (err.message || err);
                resp.style.display = 'block';
                btn.disabled = false;
                loading.style.display = 'none';
            });
        }
    </script>
</div>
