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

        if ($data['is_featured']) {
            $this->clearOtherFeatured();
        }

        $package = Package::create($data);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "' . $package->name . '" berhasil ditambahkan.');
    }

    public function update(Request $request, Package $package)
    {
        $data = $this->validatePackage($request);

        if ($data['is_featured']) {
            $this->clearOtherFeatured($package->id);
        }

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

    /**
     * Tandai paket ini sebagai "Paling Laris" (manual, ditentukan admin).
     * Hanya boleh satu paket yang berstatus laris dalam satu waktu, jadi paket
     * lain yang sebelumnya ditandai laris otomatis dilepas.
     */
    public function markFeatured(Package $package)
    {
        $this->clearOtherFeatured($package->id);
        $package->update(['is_featured' => true]);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "' . $package->name . '" sekarang ditandai sebagai Paling Laris.');
    }

    /**
     * Lepas badge "Paling Laris" dari paket ini (tidak ada paket lain yang otomatis dipilih).
     */
    public function unmarkFeatured(Package $package)
    {
        $package->update(['is_featured' => false]);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Badge Paling Laris pada paket "' . $package->name . '" berhasil dilepas.');
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
            'is_featured' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }

    private function clearOtherFeatured(?int $exceptId = null): void
    {
        Package::where('is_featured', true)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_featured' => false]);
    }
}
