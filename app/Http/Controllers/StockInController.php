<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StockInController extends Controller
{
    public function index(Request $request)
    {
        $request->merge(['type' => 'stock_in']);

        return app(StockAdjustmentController::class)->index($request);
    }

    public function store(Request $request)
    {
        $request->merge(['type' => 'stock_in']);

        return app(StockAdjustmentController::class)->store($request);
    }
}
