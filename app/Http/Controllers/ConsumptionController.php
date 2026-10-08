<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConsumptionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query();
        $date = $filters['date'] ?? now(config('app.business_timezone', 'Asia/Manila'))->toDateString();
        unset($filters['date']);

        return redirect()->route('reports.inventory', array_merge($filters, [
            'from' => $date,
            'to' => $date,
        ]));
    }
}
