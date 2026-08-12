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
    public function index(Request $request): View
    {
        return view('users.index', [
            'users' => User::where('workspace_id', $request->user()->workspace_id)
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['peranan' => 'staf'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'peranan' => ['required', 'in:admin,staf'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $data['password'] = Hash::make($data['password']);

        // Pengguna baharu sentiasa masuk ke ruang kerja admin yang menciptanya.
        $data['workspace_id'] = $request->user()->workspace_id;

        User::create($data);

        return redirect()->route('users.index')->with('status', __('wky.flash.pengguna_tambah'));
    }

    public function edit(Request $request, User $user): View
    {
        $this->pastikanSeruangKerja($request, $user);

        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->pastikanSeruangKerja($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'peranan' => ['required', 'in:admin,staf'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Menghalang admin daripada menurunkan peranannya sendiri dan hilang akses.
        if ($request->user()->is($user)) {
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

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->pastikanSeruangKerja($request, $user);

        if ($request->user()->is($user)) {
            return back()->with('ralat', __('wky.flash.pengguna_padam_sendiri'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', __('wky.flash.pengguna_padam'));
    }

    /**
     * Model User tidak berskop secara automatik seperti model lain, kerana skop
     * itu perlu membaca pengguna yang sedang log masuk dan akan berulang tanpa
     * henti. Jadi pemilikan disemak terus di sini.
     */
    private function pastikanSeruangKerja(Request $request, User $user): void
    {
        abort_unless($user->workspace_id === $request->user()->workspace_id, 404);
    }
}
