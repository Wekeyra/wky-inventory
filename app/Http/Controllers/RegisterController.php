<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'nama_syarikat' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $pengguna = DB::transaction(function () use ($data) {
            $ruangKerja = Workspace::create(['nama' => $data['nama_syarikat']]);

            // Pendaftar menjadi admin ruang kerjanya sendiri supaya dia boleh
            // menambah staf. Peranan ditetapkan di sini, bukan dari borang.
            return User::create([
                'workspace_id' => $ruangKerja->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'peranan' => 'admin',
            ]);
        });

        Auth::login($pengguna);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', __('wky.flash.daftar_berjaya'));
    }
}
