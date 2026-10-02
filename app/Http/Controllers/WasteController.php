<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WasteController extends Controller
{
    public function index(Request $request)
    {
        $request->merge(['type' => 'waste']);

        return app(StockAdjustmentController::class)->index($request);
    }

    public function store(Request $request)
    {
        $request->merge([
            'type' => 'waste',
            'waste_reason' => $request->input('reason'),
        ]);

        return app(StockAdjustmentController::class)->store($request);
    }
}
