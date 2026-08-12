<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            // Akaun yang menunggu kelulusan didahulukan supaya ia tidak terlepas pandang.
            'users' => User::orderByRaw("status = 'menunggu' DESC")->orderBy('name')->paginate(15),
            'bilMenunggu' => User::where('status', 'menunggu')->count(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['peranan' => 'staf', 'status' => 'aktif'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'peranan' => ['required', 'in:admin,staf'],
            'status' => ['required', 'in:menunggu,aktif,ditolak'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('users.index')->with('status', __('wky.flash.pengguna_tambah'));
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'peranan' => ['required', 'in:admin,staf'],
            'status' => ['required', 'in:menunggu,aktif,ditolak'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Menghalang admin daripada mengunci dirinya sendiri keluar dari sistem.
        if ($request->user()->is($user)) {
            $data['status'] = 'aktif';
            $data['peranan'] = 'admin';
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', __('wky.flash.pengguna_kemas_kini'));
    }

    public function luluskan(User $user): RedirectResponse
    {
        $user->update(['status' => 'aktif']);

        return back()->with('status', __('wky.flash.pengguna_diluluskan', ['nama' => $user->name]));
    }

    public function tolak(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('ralat', __('wky.flash.pengguna_tolak_sendiri'));
        }

        $user->update(['status' => 'ditolak']);

        return back()->with('status', __('wky.flash.pengguna_ditolak', ['nama' => $user->name]));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('ralat', __('wky.flash.pengguna_padam_sendiri'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', __('wky.flash.pengguna_padam'));
    }
}
