<?php

namespace App\Http\Controllers;

use App\Models\TaxSetting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class TaxConfigurationController extends Controller
{
    /**
     * Show the tax configuration editor.
     */
    public function edit()
    {
        $taxSetting = TaxSetting::current();

        return view('settings.tax', compact('taxSetting'));
    }

    /**
     * Update the tax configuration.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
            'is_inclusive' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $taxSetting = TaxSetting::current();
        $oldValues = $taxSetting->toArray();

        $taxSetting->update([
            'name' => trim($validated['name']),
            'rate' => round((float) $validated['rate'], 2),
            'is_inclusive' => (bool) $validated['is_inclusive'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        AuditService::logFromUser($request->user(), 'updated_tax_configuration', 'Settings', [
            'before' => $oldValues,
            'after' => $taxSetting->toArray(),
        ]);

        return redirect()->route('settings.tax.edit')->with('success', 'Tax configuration updated successfully.');
    }
}
