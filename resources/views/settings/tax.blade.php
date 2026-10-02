@extends('layouts.app')
@section('title', 'Tax Configuration')
@section('header', 'Tax Configuration')
@section('subheader', 'Configure sales tax, VAT rates, and senior citizen & PWD calculation rules')

@section('header-actions')
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Dashboard
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 text-sm shadow-xs">
        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Settings Form Card --}}
        <div class="lg:col-span-7">
            <div class="brand-card rounded-2xl p-6 shadow-sm border border-gray-100 bg-white">
                <div class="flex items-center gap-3 pb-4 mb-5 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-heim-50 border border-heim-100 flex items-center justify-center text-heim-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-base">Sales Tax & VAT Settings</h2>
                        <p class="text-xs text-gray-400">Manage tax rate, pricing model, and POS receipt display</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.tax.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="brand-label mb-1.5">Tax Name / Label <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="cfg-tax-name" value="{{ old('name', $taxSetting->name) }}"
                            placeholder="e.g. VAT" class="brand-input" required oninput="previewTax()">
                        <p class="text-[11px] text-gray-400 mt-1">Displayed on customer receipts and POS breakdown (e.g. VAT, Sales Tax).</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="brand-label mb-1.5">Tax Rate (%) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="100" name="rate" id="cfg-tax-rate"
                                    value="{{ old('rate', $taxSetting->rate) }}" class="brand-input pr-8" required oninput="previewTax()">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-gray-400">%</span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Standard rate in Philippines is 12.00%.</p>
                        </div>

                        <div>
                            <label class="brand-label mb-1.5">Pricing Model <span class="text-rose-500">*</span></label>
                            <select name="is_inclusive" id="cfg-is-inclusive" class="brand-input" onchange="previewTax()" required>
                                <option value="1" {{ old('is_inclusive', $taxSetting->is_inclusive ? '1' : '0') == '1' ? 'selected' : '' }}>
                                    Tax Inclusive (Built into menu prices)
                                </option>
                                <option value="0" {{ old('is_inclusive', $taxSetting->is_inclusive ? '1' : '0') == '0' ? 'selected' : '' }}>
                                    Tax Exclusive (Added on top)
                                </option>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Coffee shop retail prices are usually Tax Inclusive.</p>
                        </div>
                    </div>

                    <div>
                        <label class="brand-label mb-1.5">Tax System Status <span class="text-rose-500">*</span></label>
                        <select name="is_active" id="cfg-is-active" class="brand-input" onchange="previewTax()" required>
                            <option value="1" {{ old('is_active', $taxSetting->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>
                                Active (Apply tax to orders & receipts)
                            </option>
                            <option value="0" {{ old('is_active', $taxSetting->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>
                                Disabled (0% Tax / Non-taxable)
                            </option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
                        <a href="{{ route('dashboard') }}" class="brand-btn-cancel">
                            Cancel
                        </a>
                        <button type="submit" class="brand-button gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Tax Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Interactive Live Preview & BIR Rule Summary --}}
        <div class="lg:col-span-5 space-y-6">
            {{-- Live Receipt Preview --}}
            <div class="brand-card rounded-2xl p-6 shadow-sm border border-gray-100 bg-white">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Live Calculation Preview</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                        Interactive
                    </span>
                </div>

                {{-- Interactive controls for preview --}}
                <div class="space-y-3 mb-4 text-xs">
                    <div>
                        <label class="text-gray-500 block mb-1 font-semibold">Test Subtotal (₱)</label>
                        <input type="number" id="prev-subtotal" value="150" step="10" min="10"
                            class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs text-right font-mono font-bold"
                            oninput="previewTax()">
                    </div>
                    <div>
                        <label class="text-gray-500 block mb-1 font-semibold">Test Discount</label>
                        <select id="prev-discount-type" class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium" onchange="previewTax()">
                            <option value="none">No Discount (Regular)</option>
                            <option value="senior" selected>Senior Citizen (20% Off)</option>
                            <option value="pwd">PWD (20% Off)</option>
                        </select>
                    </div>
                </div>

                {{-- Mock Mini Receipt --}}
                <div class="bg-gray-50 rounded-xl p-4 font-mono text-xs border border-dashed border-gray-200 space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal:</span>
                        <span id="p-subtotal">₱150.00</span>
                    </div>
                    <div id="p-discount-row" class="flex justify-between text-rose-600 font-bold">
                        <span id="p-discount-label">Discount (20%):</span>
                        <span id="p-discount">-₱30.00</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 pt-1 border-t border-gray-200 text-sm">
                        <span>TOTAL AMOUNT:</span>
                        <span id="p-total">₱120.00</span>
                    </div>

                    <div class="pt-2 mt-2 border-t border-gray-200 text-[11px] text-gray-500 space-y-1">
                        <div class="flex justify-between">
                            <span>VATable Sales:</span>
                            <span id="p-vatable">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>VAT-Exempt Sales:</span>
                            <span id="p-exempt">₱120.00</span>
                        </div>
                        <div class="flex justify-between font-semibold text-gray-700">
                            <span id="p-tax-label">VAT (12%):</span>
                            <span id="p-tax">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Policy Information Note --}}
            <div class="p-5 rounded-2xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900 space-y-2">
                <div class="flex items-center gap-2 font-bold text-amber-950">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Philippine Statutory Exemption Compliance
                </div>
                <p class="leading-relaxed text-[11px]">
                    Under <strong>RA 9994</strong> (Senior Citizens Act) and <strong>RA 10754</strong> (PWD Act), eligible patrons receive a <strong>20% discount</strong> and are <strong>exempt from Value-Added Tax (VAT)</strong>.
                </p>
                <p class="leading-relaxed text-[11px] text-amber-800">
                    The POS terminal applies this automatically upon selecting Senior Citizen or PWD from the discount dropdown, and itemizes VAT-Exempt sales and tax information on every official receipt.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function previewTax() {
    const name = document.getElementById('cfg-tax-name')?.value || 'VAT';
    const rate = parseFloat(document.getElementById('cfg-tax-rate')?.value) || 0;
    const isInclusive = document.getElementById('cfg-is-inclusive')?.value === '1';
    const isActive = document.getElementById('cfg-is-active')?.value === '1';

    const subtotal = parseFloat(document.getElementById('prev-subtotal')?.value) || 0;
    const discType = document.getElementById('prev-discount-type')?.value || 'none';

    let discount = 0;
    let discountLabel = '';
    let total = 0;
    let vatable = 0;
    let exempt = 0;
    let tax = 0;

    if (discType === 'senior') {
        discount = subtotal * 0.20;
        discountLabel = 'Senior Citizen (20% Off):';
        total = Math.max(0, subtotal - discount);
        exempt = total;
        vatable = 0;
        tax = 0;
    } else if (discType === 'pwd') {
        discount = subtotal * 0.20;
        discountLabel = 'PWD (20% Off):';
        total = Math.max(0, subtotal - discount);
        exempt = total;
        vatable = 0;
        tax = 0;
    } else {
        discount = 0;
        const net = Math.max(0, subtotal);
        if (isActive && rate > 0) {
            if (isInclusive) {
                vatable = net / (1 + (rate / 100));
                tax = net - vatable;
                total = net;
            } else {
                vatable = net;
                tax = net * (rate / 100);
                total = net + tax;
            }
        } else {
            vatable = net;
            tax = 0;
            total = net;
        }
    }

    document.getElementById('p-subtotal').textContent = '₱' + subtotal.toFixed(2);
    const discRow = document.getElementById('p-discount-row');
    if (discount > 0) {
        discRow.classList.remove('hidden');
        document.getElementById('p-discount-label').textContent = discountLabel;
        document.getElementById('p-discount').textContent = '-₱' + discount.toFixed(2);
    } else {
        discRow.classList.add('hidden');
    }

    document.getElementById('p-total').textContent = '₱' + total.toFixed(2);
    document.getElementById('p-vatable').textContent = '₱' + vatable.toFixed(2);
    document.getElementById('p-exempt').textContent = '₱' + exempt.toFixed(2);
    document.getElementById('p-tax-label').textContent = `${name} (${rate}%):`;
    document.getElementById('p-tax').textContent = '₱' + tax.toFixed(2);
}

document.addEventListener('DOMContentLoaded', previewTax);
</script>
@endsection
