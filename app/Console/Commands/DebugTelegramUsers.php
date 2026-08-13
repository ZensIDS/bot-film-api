<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DebugTelegramUsers extends Command
{
    /**
     * php artisan telegram:debug-users
     *
     * Diagnostik buat kasus "user Telegram yang sama kok kebuat 2 baris berbeda".
     * Nunjukin telegram_id APA ADANYA (termasuk panjang karakter & representasi hex),
     * biar ketauan persis kalau ada perbedaan tak kasat mata (spasi, karakter\r,
     * atau nilai yang kepotong/berubah gara-gara casting int di suatu tempat).
     */
    protected $signature = 'telegram:debug-users';

    protected $description = 'Tampilkan semua user + telegram_id mentah (termasuk panjang & hex) untuk melacak baris duplikat';

    public function handle()
    {
        $users = User::orderByDesc('id')->limit(30)->get(['id', 'telegram_id', 'username', 'first_name', 'is_subscribed', 'expired_at', 'created_at']);

        if ($users->isEmpty()) {
            $this->warn('Tabel users masih kosong.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'telegram_id', 'len', 'hex (8 byte awal)', 'username', 'VIP?', 'expired_at', 'created_at'],
            $users->map(function ($u) {
                $raw = (string) $u->telegram_id;
                return [
                    $u->id,
                    $raw,
                    strlen($raw),
                    bin2hex(substr($raw, 0, 8)),
                    $u->username ?? '-',
                    ($u->is_subscribed && $u->expired_at && $u->expired_at->isFuture()) ? 'AKTIF' : '-',
                    $u->expired_at?->format('d M Y H:i') ?? '-',
                    $u->created_at->format('d M H:i:s'),
                ];
            })->toArray()
        );

        // Cari kandidat duplikat: telegram_id yang mirip secara numerik tapi beda string persis.
        $this->newLine();
        $this->info('Kandidat duplikat (telegram_id beda string tapi nilainya berdekatan):');

        $all = $users->pluck('telegram_id')->map(fn($v) => (string) $v)->values();
        $foundDup = false;

        foreach ($all as $i => $a) {
            foreach ($all as $j => $b) {
                if ($i >= $j) continue;
                if ($a !== $b && is_numeric($a) && is_numeric($b) && abs(((float) $a) - ((float) $b)) < 100000) {
                    $this->line("  ⚠️  \"{$a}\" (len " . strlen($a) . ") vs \"{$b}\" (len " . strlen($b) . ")");
                    $foundDup = true;
                }
            }
        }

        if (!$foundDup) {
            $this->line('  Tidak ada kandidat mencurigakan di 30 user terbaru.');
        }

        $this->newLine();
        $this->info('PHP_INT_SIZE di server ini: ' . PHP_INT_SIZE . ' byte (' . (PHP_INT_SIZE === 4 ? '32-bit — RAWAN overflow ID Telegram besar' : '64-bit — aman') . ')');
        $this->info('PHP_INT_MAX: ' . PHP_INT_MAX);

        return self::SUCCESS;
    }
}
