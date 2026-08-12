<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Peranan ditetapkan di sini, bukan daripada borang, supaya pendaftaran
        // sendiri tidak boleh menghasilkan admin.
        $pengguna = User::create($data + ['peranan' => 'staf']);

        Auth::login($pengguna);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', __('wky.flash.daftar_berjaya'));
    }
}
