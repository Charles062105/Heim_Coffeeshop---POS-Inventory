<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's read-only profile information.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information (disabled).
     */
    public function update(Request $request): RedirectResponse
    {
        return back()->with('error', 'Self-service profile updates are disabled. Please contact your system administrator.');
    }

    /**
     * Delete the user's account (disabled).
     */
    public function destroy(Request $request): RedirectResponse
    {
        return back()->with('error', 'Self-service account deletion is disabled. Please contact your system administrator.');
    }
}

