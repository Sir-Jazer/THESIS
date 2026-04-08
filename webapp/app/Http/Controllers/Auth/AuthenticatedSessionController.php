<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = match ($user->status) {
                'pending'     => 'Your account is pending approval. Please wait for an administrator to activate your account.',
                'deactivated' => 'Your account has been deactivated. Please contact an administrator.',
                'archived'    => 'Your account has been archived. Please contact an administrator.',
                default       => 'Your account is not active. Please contact an administrator.',
            };

            throw ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();

        return redirect($this->redirectByRole($user->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function redirectByRole(string $role): string
    {
        return match ($role) {
            'student'       => route('student.dashboard'),
            'proctor'       => route('proctor.dashboard'),
            'cashier'       => route('cashier.dashboard'),
            'academic_head' => route('academic-head.dashboard'),
            'admin'         => route('admin.dashboard'),
            default         => '/',
        };
    }
}
