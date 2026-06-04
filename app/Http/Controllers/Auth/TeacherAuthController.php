<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TeacherLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeacherAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.teacher-login');
    }

    public function login(TeacherLoginRequest $request): RedirectResponse
    {
        $user = User::where('phone', $request->validated('phone'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'رقم الهاتف أو كلمة المرور غير صحيحة.']);
        }

        if ($user->role !== UserRole::Teacher) {
            Auth::logout();

            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'هذا الحساب ليس حساب أستاذ.']);
        }

        if (! $user->is_active) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'تم تعطيل حسابك. تواصل مع الإدارة.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $intended = $request->query('redirect');

        if (is_string($intended) && str_starts_with($intended, url('/'))) {
            return redirect()->to($intended);
        }

        return redirect()->intended(route('teacher.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login');
    }
}
