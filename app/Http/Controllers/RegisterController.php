<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Peranan dan status ditetapkan di sini, bukan daripada borang, supaya
        // pendaftaran sendiri tidak boleh menghasilkan admin atau akaun aktif.
        User::create($data + [
            'peranan' => 'staf',
            'status' => 'menunggu',
        ]);

        return redirect()->route('login')->with('status', __('wky.flash.daftar_menunggu'));
    }
}
