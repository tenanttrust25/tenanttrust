<x-filament-panels::page>
    @php
        $stripeKey = config('services.stripe.key');
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $intent = \Stripe\PaymentIntent::create([
            'amount' => (int) round($record->amount * 100),
            'currency' => 'cad',
            'metadata' => [
                'invoice_id' => $record->id,
                'invoice_number' => $record->invoice_number,
            ],
        ]);
        $clientSecret = $intent->client_secret;
    @endphp

    <div class="max-w-lg mx-auto">
        {{-- Invoice Summary Card --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Invoice #{{ $record->invoice_number }}</h2>
            <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <p><span class="font-medium">Amount:</span> ${{ number_format($record->amount, 2) }}</p>
                <p><span class="font-medium">Due Date:</span> {{ $record->due_date?->format('F j, Y') }}</p>
                <p><span class="font-medium">Status:</span>
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $record->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400' : '' }}
                        {{ $record->status === 'overdue' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400' : '' }}">
                        {{ $record->status === 'pending' ? 'Unpaid' : ucfirst($record->status) }}
                    </span>
                </p>
            </div>
        </div>

        {{-- Step 1: Card Entry --}}
        <div id="step-card-entry" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Enter Card Details</h3>

            <form id="payment-form" onsubmit="return false;">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $record->id }}">
                <input type="hidden" id="client-secret" value="{{ $clientSecret }}">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Card Number</label>
                        <div id="card-number-element" class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-700"></div>
                        <div id="card-number-errors" class="text-xs text-red-500 mt-1"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expiry Date</label>
                            <div id="card-expiry-element" class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-700"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CVC</label>
                            <div id="card-cvc-element" class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>

                <div id="card-entry-error" class="mt-4 p-3 rounded-lg border text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800" style="display:none;"></div>

                <div class="mt-6">
                    <button type="button" id="review-btn" onclick="reviewPayment()"
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed text-white font-bold rounded-lg transition-colors text-base">
                        Continue to Review
                    </button>
                    <a href="{{ \App\Filament\Tenant\Resources\InvoiceResource::getUrl('index') }}"
                       class="block text-center mt-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
                </div>
            </form>
        </div>

        {{-- Step 2: Confirmation (hidden initially) --}}
        <div id="step-confirm" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6" style="display:none;">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Confirm Payment</h3>

            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-4 mb-4 text-sm">
                <table class="w-full">
                    <tr>
                        <td class="py-2 font-medium text-gray-500 dark:text-gray-400">Invoice</td>
                        <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">{{ $record->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium text-gray-500 dark:text-gray-400">Billed To</td>
                        <td class="py-2 text-right text-gray-900 dark:text-white">{{ $record->tenant?->name }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium text-gray-500 dark:text-gray-400">Property</td>
                        <td class="py-2 text-right text-gray-900 dark:text-white">{{ $record->property?->title }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium text-gray-500 dark:text-gray-400">Card</td>
                        <td class="py-2 text-right text-gray-900 dark:text-white" id="confirm-card-last4">••••</td>
                    </tr>
                    <tr class="border-t border-gray-200 dark:border-gray-600">
                        <td class="py-3 font-bold text-gray-900 dark:text-white text-base">Total</td>
                        <td class="py-3 text-right font-bold text-gray-900 dark:text-white text-lg">
                            ${{ number_format($record->amount, 2) }}
                        </td>
                    </tr>
                </table>
            </div>

            <div id="payment-loading" class="flex items-center justify-center py-3" style="display:none;">
                <svg class="animate-spin h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">Processing payment...</span>
            </div>

            <div id="payment-message" class="p-3 rounded-lg border text-sm" style="display:none;"></div>

            <div class="mt-6 space-y-3">
                <button type="button" onclick="confirmPayment()" id="confirm-btn"
                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed text-white font-bold rounded-lg transition-colors text-base">
                    Confirm & Pay ${{ number_format($record->amount, 2) }}
                </button>
                <button type="button" onclick="backToCardEntry()" id="back-btn"
                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors text-base">
                    ← Change Card Details
                </button>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (function() {
            var stripe = Stripe('{{ $stripeKey }}');
            var clientSecret = document.getElementById('client-secret').value;
            var invoiceId = {{ $record->id }};
            var csrfToken = document.querySelector('input[name=_token]').value;
            var stripeCard = null;
            var last4 = '';
            var brand = '';

            var elements = stripe.elements();
            var style = {
                base: {
                    fontSize: '16px',
                    fontFamily: 'Arial, sans-serif',
                    color: '#1f2937',
                    '::placeholder': { color: '#9ca3af' },
                },
            };

            var cardNumber = elements.create('cardNumber', { style: style, showIcon: true });
            cardNumber.mount('#card-number-element');
            cardNumber.on('change', function(e) {
                if (e.complete) {
                    last4 = e.brand + ' ending in ' + e.last4;
                    brand = e.brand;
                }
            });

            var cardExpiry = elements.create('cardExpiry', { style: style });
            cardExpiry.mount('#card-expiry-element');

            var cardCvc = elements.create('cardCvc', { style: style });
            cardCvc.mount('#card-cvc-element');

            window.reviewPayment = function() {
                var errEl = document.getElementById('card-entry-error');
                errEl.style.display = 'none';

                if (!last4) {
                    errEl.innerHTML = '\u274C Please enter a complete card number.';
                    errEl.style.display = 'block';
                    return;
                }

                document.getElementById('confirm-card-last4').textContent = last4;
                document.getElementById('step-card-entry').style.display = 'none';
                document.getElementById('step-confirm').style.display = 'block';
            };

            window.backToCardEntry = function() {
                document.getElementById('step-confirm').style.display = 'none';
                document.getElementById('payment-message').style.display = 'none';
                document.getElementById('payment-loading').style.display = 'none';
                document.getElementById('step-card-entry').style.display = 'block';
            };

            window.confirmPayment = async function() {
                var confirmBtn = document.getElementById('confirm-btn');
                var backBtn = document.getElementById('back-btn');
                var loading = document.getElementById('payment-loading');
                var msg = document.getElementById('payment-message');

                confirmBtn.disabled = true;
                backBtn.disabled = true;
                loading.style.display = 'flex';
                msg.style.display = 'none';

                try {
                    var result = await stripe.confirmCardPayment(clientSecret, {
                        payment_method: { card: cardNumber }
                    });

                    if (result.error) {
                        msg.className = 'p-3 rounded-lg border text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800';
                        msg.innerHTML = '\u274C ' + result.error.message;
                        msg.style.display = 'block';
                        confirmBtn.disabled = false;
                        backBtn.disabled = false;
                        loading.style.display = 'none';
                        return;
                    }

                    if (result.paymentIntent.status === 'succeeded') {
                        var res = await fetch('/api/stripe/confirm', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                invoice_id: invoiceId,
                                payment_intent_id: result.paymentIntent.id
                            })
                        });

                        var data = await res.json();

                        if (data.success) {
                            msg.className = 'p-3 rounded-lg border text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800';
                            msg.innerHTML = '\u2705 ' + data.message;
                            msg.style.display = 'block';
                            loading.style.display = 'none';
                            confirmBtn.style.display = 'none';
                            backBtn.style.display = 'none';
                            setTimeout(function() {
                                window.location.href = @json(\App\Filament\Tenant\Resources\InvoiceResource::getUrl('index'));
                            }, 1500);
                        } else {
                            msg.className = 'p-3 rounded-lg border text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800';
                            msg.innerHTML = '\u274C ' + data.message;
                            msg.style.display = 'block';
                            confirmBtn.disabled = false;
                            backBtn.disabled = false;
                            loading.style.display = 'none';
                        }
                    }
                } catch (err) {
                    msg.className = 'p-3 rounded-lg border text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800';
                    msg.innerHTML = '\u274C Error: ' + err.message;
                    msg.style.display = 'block';
                    confirmBtn.disabled = false;
                    backBtn.disabled = false;
                    loading.style.display = 'none';
                }
            };
        })();
    </script>
</x-filament-panels::page>
