{{-- Status Badge Component --}}
@php
$map = [
    'completed'        => 'bg-heim-100 text-heim-700',
    'pending'          => 'bg-yellow-100 text-yellow-700',
    'cancelled'        => 'bg-gray-100 text-gray-600',
    'refunded'         => 'bg-blue-100 text-blue-700',
    'active'           => 'bg-green-100 text-green-700',
    'inactive'         => 'bg-red-100 text-red-700',
    'good'             => 'bg-heim-100 text-heim-700',
    'low_stock'        => 'bg-amber-100 text-amber-700',
    'out_of_stock'     => 'bg-red-100 text-red-700',
    'paid'             => 'bg-heim-100 text-heim-700',
    'partially_paid'   => 'bg-amber-100 text-amber-700',
    'failed'           => 'bg-red-100 text-red-700',
    'voided'           => 'bg-rose-100 text-rose-700',
    'cash'             => 'bg-gray-100 text-gray-700',
    'online'           => 'bg-blue-100 text-blue-700',
    'stock_in'         => 'bg-heim-100 text-heim-700',
    'sales_consumption'=> 'bg-orange-100 text-orange-700',
    'sales_return'     => 'bg-green-100 text-green-700',
    'waste'            => 'bg-red-100 text-red-700',
    'adjustment_add'   => 'bg-blue-100 text-blue-700',
    'adjustment_deduct'=> 'bg-rose-100 text-rose-700',
    'owner'            => 'bg-purple-100 text-purple-700',
    'manager'          => 'bg-blue-100 text-blue-700',
    'cashier'          => 'bg-gray-100 text-gray-600',
];
$labels = [
    'cash'     => 'Cash',
    'online'   => 'Online Payment',
    'active'   => 'Unarchived',
    'inactive' => 'Archived',
    'partially_paid' => 'Partially Paid',
    'adjustment_deduct' => 'Stock-Out',
];
$label = $labels[$status ?? ''] ?? str_replace('_', ' ', ucfirst($status ?? ''));
$cls = $map[$status ?? ''] ?? 'bg-gray-100 text-gray-600';
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cls }}">{{ $label }}</span>
