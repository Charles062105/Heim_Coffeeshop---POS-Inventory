<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $order->order_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Standard 80mm Thermal Receipt Specifications */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        @media print {
            html, body {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'Courier New', Courier, monospace !important;
                display: block !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-wrapper {
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 4mm 2mm !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
            }
            * {
                color: #000000 !important;
                background-color: transparent !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }
        }

        @media screen {
            body {
                background-color: #f1f5f9;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Courier New', monospace;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 2rem 1rem;
            }
            .receipt-wrapper {
                width: 330px;
                max-width: 100%;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.06);
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body class="antialiased">

    {{-- Screen action bar (hidden during print) --}}
    <div class="no-print w-full max-w-[330px] mb-4 flex items-center justify-between gap-2">
        <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-gray-600 hover:text-emerald-800 bg-white border border-gray-200 px-3 py-2 rounded-xl shadow-xs transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to POS
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-2 rounded-xl shadow-xs transition-colors">
                Details
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 px-3.5 py-2 rounded-xl shadow-sm transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </button>
        </div>
    </div>

    {{-- Printable receipt card (standard 80mm format) --}}
    <div class="receipt-wrapper">
        @include('orders.partials.receipt-content', ['order' => $order])
    </div>

    <script>
        // Auto-print if autoprint query parameter is passed
        window.addEventListener('load', function () {
            const params = new URLSearchParams(window.location.search);
            if (params.get('autoprint') === '1') {
                setTimeout(function () {
                    window.print();
                }, 200);
            }
        });
    </script>
</body>
</html>
