@extends('admin.layout')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-6">
    <p class="text-sm">Selamat datang, <b class="text-[var(--gold-soft)]">{{ Auth::guard('admin')->user()->name }}</b> 👋</p>
    <p class="text-xs text-[var(--text-muted)] mt-2 leading-relaxed">
        Dashboard ini masih placeholder. Menu Manajemen Film, User & Langganan,
        Riwayat Transaksi, dan Request Film akan dibangun bertahap di langkah selanjutnya.
    </p>
</div>
@endsection
