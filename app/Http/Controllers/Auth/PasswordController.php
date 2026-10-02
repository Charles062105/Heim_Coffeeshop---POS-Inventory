<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    /**
     * Password updates are disabled for self-service.
     */
    public function update(Request $request): RedirectResponse
    {
        return back()->with('error', 'Self-service password changes are disabled. Please contact your system administrator.');
    }
}

