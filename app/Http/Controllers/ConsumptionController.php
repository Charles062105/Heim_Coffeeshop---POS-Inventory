<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConsumptionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query();
        $date = $filters['date'] ?? now()->toDateString();
        unset($filters['date']);

        return redirect()->route('adjustments.index', array_merge($filters, [
            'from' => $date,
            'to' => $date,
            'type' => 'sales_consumption',
        ]));
    }
}
