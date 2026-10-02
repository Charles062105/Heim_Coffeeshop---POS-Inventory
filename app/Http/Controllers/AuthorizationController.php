<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthorizationController extends Controller
{
    /**
     * Verify an authorizer's credentials and return their role.
     * Used by JS fetch calls from the auth modal.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('status', 'active')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $user->canAuthorize()) {
            return response()->json([
                'success' => false,
                'message' => 'This account does not have authorization privileges.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'name' => $user->name,
            'role' => $user->role,
        ]);
    }
}
