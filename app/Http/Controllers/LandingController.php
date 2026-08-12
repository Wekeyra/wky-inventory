<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingController extends Controller
{
    /*
     | Halaman pendaratan awam.
     |
     | Pengguna yang sudah log masuk dihantar terus ke dashboard. Mereka sudah
     | memilih untuk menggunakan sistem, jadi halaman pemasaran hanya menambah
     | satu klik sebelum kerja sebenar — dan '/' ialah URL yang paling kerap
     | ditanda buku.
     */
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('landing');
    }
}
