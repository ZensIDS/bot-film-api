<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::query()
            ->withCount('transactions')
            ->orderBy('price')
            ->get();

        return view('admin.packages.index', compact('packages'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePackage($request);

        Package::create($data);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "' . $data['name'] . '" berhasil ditambahkan.');
    }

    public function update(Request $request, Package $package)
    {
        $data = $this->validatePackage($request);

        $package->update($data);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "' . $package->name . '" berhasil diperbarui.');
    }

    /**
     * Aktif/nonaktifkan paket dengan cepat (tanpa buka form edit).
     * Paket nonaktif otomatis tidak tampil lagi di TWA (lihat GET /api/packages).
     */
    public function toggleActive(Package $package)
    {
        $package->update(['is_active' => !$package->is_active]);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "' . $package->name . '" sekarang berstatus ' . ($package->is_active ? 'Aktif' : 'Nonaktif') . '.');
    }

    public function destroy(Package $package)
    {
        if ($package->transactions()->exists()) {
            return redirect()
                ->route('admin.packages.index')
                ->with('error', 'Paket "' . $package->name . '" tidak bisa dihapus karena sudah pernah dipakai transaksi. Nonaktifkan saja paketnya.');
        }

        $name = $package->name;
        $package->delete();

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "' . $name . '" berhasil dihapus.');
    }

    private function validatePackage(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1|max:3650',
            'price' => 'required|numeric|min:0|max:999999999',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
