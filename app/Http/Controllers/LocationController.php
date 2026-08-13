<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('locations.index', [
            'locations' => Location::query()
                ->withCount(['balances as produk_count' => fn ($q) => $q->where('kuantiti', '>', 0)])
                ->withSum('balances as jumlah_unit', 'kuantiti')
                ->orderByDesc('lalai')
                ->orderBy('nama')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('locations.form', ['location' => new Location(['aktif' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->simpan($request, new Location());

        return redirect()->route('locations.index')->with('status', __('wky.flash.lokasi_tambah'));
    }

    public function show(Location $location): View
    {
        return view('locations.show', [
            'location' => $location,
            'balances' => $location->balances()
                ->with('product')
                ->where('kuantiti', '>', 0)
                ->orderBy('kuantiti', 'desc')
                ->paginate(20),
        ]);
    }

    public function edit(Location $location): View
    {
        return view('locations.form', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $this->simpan($request, $location);

        return redirect()->route('locations.index')->with('status', __('wky.flash.lokasi_kemas_kini'));
    }

    /**
     * Lokasi hanya boleh dipadam apabila ia kosong dan bukan lokasi lalai.
     *
     * Memadam gudang yang masih ada stok bermakna baki itu lenyap tanpa
     * sebarang pergerakan yang menerangkannya — jumlah produk akan kekal
     * sedangkan tiada gudang yang memegangnya lagi. Kosongkan dahulu dengan
     * memindahkan stoknya ke gudang lain.
     */
    public function destroy(Location $location): RedirectResponse
    {
        if ($location->lalai) {
            return back()->with('ralat', __('wky.flash.lokasi_lalai_kekal'));
        }

        if ($location->adaStok()) {
            return back()->with('ralat', __('wky.flash.lokasi_ada_stok'));
        }

        $location->delete();

        return redirect()->route('locations.index')->with('status', __('wky.flash.lokasi_padam'));
    }

    private function simpan(Request $request, Location $location): void
    {
        $data = $request->validate([
            'kod' => ['required', 'string', 'max:30', Rule::unique('locations', 'kod')
                ->where('workspace_id', $request->user()->workspace_id)
                ->ignore($location)],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'lalai' => ['nullable', 'boolean'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $data['aktif'] = $request->boolean('aktif');
        // Lokasi lalai tidak boleh dinyahtanda terus: setiap ruang kerja mesti
        // ada satu, kerana aliran yang tidak bertanya lokasi perlu mendarat di
        // suatu tempat. Ia berpindah dengan menandakan lokasi lain sebagai lalai.
        $data['lalai'] = $location->lalai || $request->boolean('lalai');

        DB::transaction(function () use ($data, $location) {
            $location->fill($data)->save();

            if ($location->lalai) {
                Location::whereKeyNot($location->id)->where('lalai', true)->update(['lalai' => false]);
            }
        });
    }
}
