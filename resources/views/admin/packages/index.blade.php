@extends('admin.layout')

@section('title', 'Paket & Harga')
@section('page_title', 'Paket & Harga')

@section('content')

<div class="flex items-center justify-between gap-4 mb-5 flex-wrap">
    <p class="text-sm text-[var(--text-muted)]">
        Paket yang berstatus <span class="text-green-300 font-semibold">Aktif</span> akan tampil di tab "Paket" pada Mini App Telegram, diurutkan dari harga termurah ke termahal.
    </p>
    <button type="button" onclick="openPackageModal()" class="text-xs font-semibold px-4 py-2 rounded-xl btn-gold whitespace-nowrap">
        + Tambah Paket
    </button>
</div>

<div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[var(--text-muted)] text-xs uppercase tracking-wide border-b border-[var(--hairline)]">
                <th class="px-5 py-3">Nama Paket</th>
                <th class="px-5 py-3">Durasi</th>
                <th class="px-5 py-3">Harga</th>
                <th class="px-5 py-3">Terpakai</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($packages as $package)
                <tr class="border-b border-[var(--hairline)] last:border-0 hover:bg-[var(--surface-2)]/40">
                    <td class="px-5 py-3">
                        <p class="font-semibold text-white">{{ $package->name }}</p>
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">{{ $package->duration_days }} Hari</td>
                    <td class="px-5 py-3 text-[var(--text)] font-semibold">Rp {{ number_format($package->price, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">{{ $package->transactions_count }}x</td>
                    <td class="px-5 py-3">
                        @if ($package->is_active)
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-green-900/30 text-green-300">Aktif</span>
                        @else
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[var(--surface-2)] text-[var(--text-muted)]">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <button type="button"
                                onclick="openPackageModal({{ Illuminate\Support\Js::from([
                                    'id' => $package->id,
                                    'name' => $package->name,
                                    'duration_days' => $package->duration_days,
                                    'price' => (float) $package->price,
                                    'is_active' => $package->is_active,
                                    'action' => route('admin.packages.update', $package),
                                ]) }})"
                                class="text-xs font-semibold text-[var(--gold-soft)] hover:text-[var(--gold)] whitespace-nowrap">
                                Edit
                            </button>

                            <form action="{{ route('admin.packages.toggle-active', $package) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-[var(--text-muted)] hover:text-white whitespace-nowrap">
                                    {{ $package->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            <button type="button"
                                onclick="openDeletePackageModal('{{ route('admin.packages.destroy', $package) }}', {{ Illuminate\Support\Js::from($package->name) }})"
                                class="text-xs font-semibold text-[var(--crimson)] hover:text-[#F27C97] whitespace-nowrap">
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[var(--text-muted)] text-sm">
                        Belum ada paket. Klik "+ Tambah Paket" untuk membuat paket pertama.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- ===================== Modal: Tambah / Edit Paket ===================== -->
<div id="packageModalBackdrop" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl w-full max-w-sm p-6 shadow-2xl">
        <h3 id="packageModalTitle" class="font-display text-lg font-semibold text-white mb-5">Tambah Paket</h3>

        <form id="packageForm" method="POST" action="{{ route('admin.packages.store') }}">
            @csrf
            <div id="packageMethodField"></div>

            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-2">Nama Paket</label>
            <input type="text" name="name" id="packageName" required maxlength="255"
                placeholder="mis. Paket Bulanan (30 Hari)"
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white mb-4 focus:outline-none focus:border-[var(--gold)]">

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-2">Durasi (Hari)</label>
                    <input type="number" name="duration_days" id="packageDuration" required min="1" max="3650" value="30"
                        class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-2">Harga (Rp)</label>
                    <input type="number" name="price" id="packagePrice" required min="0" step="100" value="10000"
                        class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
                </div>
            </div>

            <label class="flex items-center gap-2 mb-6 cursor-pointer select-none">
                <input type="checkbox" name="is_active" id="packageIsActive" value="1" checked
                    class="w-4 h-4 rounded accent-[var(--gold)]">
                <span class="text-xs text-[var(--text)]">Aktifkan paket ini (tampil di Mini App)</span>
            </label>

            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closePackageModal()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
                    Batal
                </button>
                <button type="submit" id="packageSubmitBtn"
                    class="text-xs font-semibold px-5 py-2 rounded-xl btn-gold">
                    Simpan Paket
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== Modal: Confirm Hapus Paket ===================== -->
<div id="deletePackageModalBackdrop" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl w-full max-w-sm p-6 shadow-2xl">
        <div class="w-11 h-11 rounded-full bg-red-900/30 flex items-center justify-center text-xl mb-4">
            ⚠️
        </div>
        <h3 class="font-display text-lg font-semibold text-white mb-1">Hapus Paket?</h3>
        <p class="text-sm text-[var(--text-muted)] mb-6">
            Paket <span id="deletePackageName" class="text-white font-semibold">—</span> akan dihapus permanen dan tidak bisa dikembalikan. Paket yang sudah pernah dipakai transaksi tidak bisa dihapus — nonaktifkan saja.
        </p>

        <form id="deletePackageForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeDeletePackageModal()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
                    Batal
                </button>
                <button type="submit"
                    class="text-xs font-semibold px-5 py-2 rounded-xl bg-[var(--crimson)] text-white hover:brightness-110">
                    Ya, Hapus Paket
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('extra_js')
<script>
    function openPackageModal(pkg) {
        const form = document.getElementById('packageForm');
        const methodField = document.getElementById('packageMethodField');

        if (pkg && pkg.id) {
            document.getElementById('packageModalTitle').textContent = 'Edit Paket';
            form.action = pkg.action;
            methodField.innerHTML = '@method('PUT')';
            document.getElementById('packageName').value = pkg.name;
            document.getElementById('packageDuration').value = pkg.duration_days;
            document.getElementById('packagePrice').value = pkg.price;
            document.getElementById('packageIsActive').checked = !!pkg.is_active;
        } else {
            document.getElementById('packageModalTitle').textContent = 'Tambah Paket';
            form.action = "{{ route('admin.packages.store') }}";
            methodField.innerHTML = '';
            document.getElementById('packageName').value = '';
            document.getElementById('packageDuration').value = 30;
            document.getElementById('packagePrice').value = 10000;
            document.getElementById('packageIsActive').checked = true;
        }

        const backdrop = document.getElementById('packageModalBackdrop');
        backdrop.classList.remove('hidden');
        backdrop.classList.add('flex');
    }
    function closePackageModal() {
        const backdrop = document.getElementById('packageModalBackdrop');
        backdrop.classList.add('hidden');
        backdrop.classList.remove('flex');
    }

    function openDeletePackageModal(actionUrl, packageName) {
        document.getElementById('deletePackageForm').action = actionUrl;
        document.getElementById('deletePackageName').textContent = packageName;
        const backdrop = document.getElementById('deletePackageModalBackdrop');
        backdrop.classList.remove('hidden');
        backdrop.classList.add('flex');
    }
    function closeDeletePackageModal() {
        const backdrop = document.getElementById('deletePackageModalBackdrop');
        backdrop.classList.add('hidden');
        backdrop.classList.remove('flex');
    }

    document.getElementById('packageModalBackdrop').addEventListener('click', function (e) {
        if (e.target === this) closePackageModal();
    });
    document.getElementById('deletePackageModalBackdrop').addEventListener('click', function (e) {
        if (e.target === this) closeDeletePackageModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePackageModal();
            closeDeletePackageModal();
        }
    });
</script>
@endsection
