# Laporan Audit Kode — Maxy Performance Management

> Dibuat: 2026-06-25 · Cakupan: seluruh backend (controllers, models, jobs, services, middleware, deployment) + UX frontend.
> Metodologi: bug-hunting + deletion test / deepening (improve-codebase-architecture) + checklist UX (ui-ux-pro-max).
> Status verifikasi saat audit: `php artisan test` → 201/201 hijau, `npm run build` ✅.

## Status revisi (update 2026-06-25)

| Item | Status | Catatan |
|------|--------|---------|
| **C3** Password reset tiap deploy | ✅ **Fixed** | `UserSeeder` kini set password **create-only** (`firstOrNew` + cek `!exists`). Regression test `UserSeederTest`. |
| **C4** Akun dummy di produksi | ✅ **Fixed** | Akun ber-nama "Dummy"/"Testing" di-skip saat `app()->isProduction()`. |
| **C1** Worker + scheduler mati | ⚠️ **Mitigated** | `nixpacks.toml`: hapus seed per-start, jalankan `queue:work` + `schedule:work` background. **Butuh keputusan infra** utk worker auto-restart terpisah. |
| **C2** `php -S` di produksi | 📝 **Didokumentasikan** | Butuh keputusan platform (nginx+php-fpm / FrankenPHP). Belum diubah. |
| **H1** AI gagal → 500 submit | ✅ **Fixed** | Dispatch `->afterResponse()` + method `failed()` di job. |
| **H3** Evidence non-transaksional | ✅ **Fixed** | `store()` bungkus entry+evidence dalam `DB::transaction`, dispatch setelah commit. |
| **M1** Drift "48 jam" vs 10 jam | ✅ **Fixed** | Konstanta `DailyTaskEntry::REVISION_WINDOW_HOURS=10` dipakai di model + view + komentar. |
| **U1** Search backdate fixed-width | ✅ **Fixed** | Jadi `flex:1 1 220px;min-width:200px`. |
| H2, M2–M5, L1–L4, A1–A3, U2–U8 | ⏳ **Belum** | Lihat detail di bawah. |

Verifikasi pasca-revisi: `php artisan test` → **204/204 hijau**, `npm run build` ✅.

---

## Cara membaca

| Severity | Arti |
|----------|------|
| 🔴 **CRITICAL** | Sudah/akan menyebabkan kegagalan fungsi atau lubang keamanan di produksi. Perbaiki segera. |
| 🟠 **HIGH** | Bug nyata yang muncul pada kondisi umum (error API, beban, race). |
| 🟡 **MEDIUM** | Bug laten / inkonsistensi yang menyesatkan atau pecah di edge case. |
| 🔵 **LOW** | Kebersihan kode, dead code, dokumentasi. |
| 🏛️ **ARCH** | Peluang "deepening" arsitektur (testability + navigasi AI). |

---

## 🔴 CRITICAL

### C1 — Produksi tidak menjalankan queue worker maupun scheduler
**Lokasi:** `nixpacks.toml` → `[start]`
```
php artisan migrate --force && php artisan db:seed --class=UserSeeder --force && php -S 0.0.0.0:$PORT -t public
```
**Masalah:** `QUEUE_CONNECTION=database` dan ada job yang di-`dispatch` (`EvaluateDailyTaskJob`, `GenerateWeeklyGapAnalysisJob`, `GenerateMonthlyGapAnalysisJob`, `GenerateWorkloadReportJob`) **tetapi start command tidak menjalankan `php artisan queue:work`**. Juga ada `Schedule::command('tasks:auto-reject-revisions')->hourly()` dll di `routes/console.php`, **tetapi tidak ada `schedule:run` (cron)**.
**Dampak:**
- Semua evaluasi AI masuk tabel `jobs` dan **tidak pernah diproses** → badge "AI sedang memproses…" menggantung selamanya, `aiEvaluation` selalu null, tabel `jobs` membengkak tanpa batas.
- Gap analysis & workload report tidak pernah ter-generate.
- Auto-reject revisi kedaluwarsa hanya jalan "lazy" saat laporan dibuka; cleanup notifikasi mingguan & `queue:flush` tidak pernah jalan.
**Perbaikan:** Jalankan worker + scheduler sebagai proses terpisah (mis. `supervisor`, atau Procfile dengan beberapa proses, atau container terpisah):
```
php artisan queue:work --tries=3 --max-time=3600
php artisan schedule:work   # atau cron */1 * * * * php artisan schedule:run
```
Jangan gabung ke satu `php -S`. Lihat juga C2.

### C2 — Server produksi memakai `php -S` (server dev single-thread)
**Lokasi:** `nixpacks.toml` `[start]`.
**Masalah:** `php -S` adalah built-in server PHP untuk development — **single-threaded, tanpa concurrency, tidak untuk produksi**. Satu request lambat (mis. panggilan AI sinkron, unggah file) memblok semua user lain.
**Perbaikan:** Pakai `nginx + php-fpm` atau `FrankenPHP`/`RoadRunner`. Minimal `php artisan serve` pun tetap single-process — bukan solusi.

### C3 — Password semua user ter-reset ke default tiap deploy
**Lokasi:** `database/seeders/UserSeeder.php` + `nixpacks.toml` (`db:seed --class=UserSeeder --force` jalan **tiap start**).
```php
User::updateOrCreate(
    ['email' => $data['email']],
    [ ... 'password' => Hash::make('maxy2026'), ... ] // ← di blok update
);
```
**Masalah:** `password` ada di atribut update `updateOrCreate`. Karena seeder dijalankan setiap restart container, **password setiap user (termasuk leader & C-Level) di-reset paksa ke `maxy2026` pada setiap deploy/restart** — menimpa password yang sudah diubah user.
**Dampak:** Lubang keamanan + akun terkunci dari perspektif user (password mereka "hilang").
**Perbaikan:**
1. Pindahkan `password` (dan field yang user boleh ubah seperti `name`) ke argumen *create-only* — gunakan pola: cek `firstOrNew`, set password **hanya jika** `!$user->exists`.
2. Jangan jalankan `db:seed` pada setiap start produksi; jadikan langkah manual/one-off.
3. Ganti default `maxy2026` dengan password acak per-user atau paksa setup via Google.

### C4 — Akun default-credential ikut ter-seed ke produksi
**Lokasi:** `UserSeeder.php` — `superadmin@maxy.academy` & `staff@maxy.academy`, password `maxy2026`.
**Masalah:** Akun "Dummy" dengan kredensial yang dapat ditebak masuk ke DB produksi (seeder jalan tiap start). Super-admin dummy = akses penuh.
**Perbaikan:** Pisahkan seeder demo dari seeder produksi; jangan pernah seed akun dummy di produksi. Hapus akun dummy yang sudah terlanjur ada.

---

## 🟠 HIGH

### H1 — Kegagalan AI bisa men-500-kan submit laporan (jika queue jatuh ke sync)
**Lokasi:** `EvaluateDailyTaskJob::handle()` lempar `throw new \Exception(...)` saat Groq gagal; `DailyTaskEntryController::store()` mem-`dispatch` job.
**Masalah:** Saat ini `QUEUE_CONNECTION=database` jadi aman. Tapi **bila env produksi tak ter-set / fallback ke `sync`** (umum terjadi saat `.env` belum lengkap), `dispatch()` mengeksekusi job inline; exception-nya menggelembung ke request → **staff dapat 500 padahal entry sudah dibuat** (terbukti dari kegagalan `DailyTaskGuardsTest` yang flaky).
**Perbaikan:**
- Tambah method `failed(\Throwable $e)` di job untuk logging anggun, dan jangan `throw` untuk kondisi yang bukan transient (mis. respons AI invalid) — bedakan retryable vs permanen.
- Pertimbangkan `EvaluateDailyTaskJob::dispatch(...)->afterResponse()` agar tak pernah memblok/menggagalkan response.
- Set eksplisit `QUEUE_CONNECTION=database` di env produksi (jangan andalkan default).

### H2 — Tidak ada `failed_jobs` handling + `queue:flush` harian menghapus jejak
**Lokasi:** `routes/console.php` → `Schedule::command('queue:flush')->daily()`.
**Masalah:** `queue:flush` menghapus **semua failed jobs** tiap hari. Jika nanti worker dijalankan (lihat C1), kegagalan AI akan hilang sebelum sempat diinvestigasi. Tidak ada alert saat job gagal.
**Perbaikan:** Hapus jadwal `queue:flush` otomatis (atau ganti jadi `queue:prune-failed --hours=168`). Tambah notifikasi saat job gagal.

### H3 — Unggah evidence tidak transaksional dengan pembuatan entry
**Lokasi:** `DailyTaskEntryController::store()` (dan `update()`).
**Masalah:** `DailyTaskEntry::create()` lalu loop simpan file/evidence **tanpa** `DB::transaction`. Jika salah satu `store()` file gagal di tengah, entry tetap tersimpan dengan bukti tidak lengkap; file orphan bisa tertinggal di storage. Hapus evidence di `update()` menghapus file storage sebelum commit DB.
**Perbaikan:** Bungkus pembuatan entry + evidence dalam satu transaksi; hapus file storage **setelah** commit (atau via job pembersih), bukan di tengah.

---

## 🟡 MEDIUM

### M1 — Window revisi: dokumentasi "48 jam" vs kode "10 jam"
**Lokasi:** `DailyTaskEntry::canBeRevised()` (`addHours(10)`) vs PHPDoc-nya (baris ~171 "48 jam") dan komentar `DailyTaskEntryController::sendToRevision()` (baris ~668 "48 jam").
**Masalah:** Dokumentasi menyesatkan; developer berikutnya bisa salah mengubah logika. Pesan UI ("Masa revisi 10 jam") sudah benar.
**Perbaikan:** Samakan komentar ke 10 jam, atau ekstrak konstanta `REVISION_WINDOW_HOURS = 10` dan pakai di model + view + pesan.

### M2 — Efek samping tulis di request GET (`show`)
**Lokasi:** `DailyTaskEntryController::show()` memanggil `$dailyTask->autoRejectExpiredRevision()` yang **menulis DB + mengirim notifikasi**.
**Masalah:** GET seharusnya idempoten/aman. Di sini membuka detail (termasuk oleh CEO/leader yang sekadar melihat) dapat memicu transisi `revision → rejected` + notifikasi. Rentan ter-trigger oleh prefetch browser/crawler. Logikanya sudah atomik (aman dari double-notify) tetapi tempatnya salah.
**Perbaikan:** Pindahkan auto-reject sepenuhnya ke scheduled command `tasks:auto-reject-revisions` (yang harus benar-benar jalan — lihat C1). Hilangkan pemanggilan dari `show()`.

### M3 — `progressHistory()` query berantai (N+1)
**Lokasi:** `DailyTaskEntry::progressHistory()` — `while` naik ke root lalu turun, satu `->first()` per hop, dipanggil pada setiap `show()`.
**Masalah:** Rantai N-hari = ~2N query. Tidak fatal tapi tumbuh seiring tugas berhari-hari.
**Perbaikan:** Muat seluruh rantai sekali (query by root id), atau simpan `root_entry_id` denormalized agar satu query mengambil semua.

### M4 — `EnsureActive` tidak memutus sesi user yang dihapus (bukan sekadar nonaktif)
**Lokasi:** `EnsureActive` hanya cek `!$user->is_active`.
**Masalah:** Jika baris user dihapus saat sesi aktif, `Auth::user()` bisa null di tengah jalan dan menyebabkan error di kode yang mengasumsikan user ada. (Sebagian besar route sudah `auth`, tetapi worth a guard.)
**Perbaikan:** Tidak mendesak; pastikan kebijakan "nonaktifkan, jangan hapus" + tambahkan null-guard di tempat yang membaca `$user->department`.

### M5 — Verifikasi otorisasi method `destroy` yang tumpang tindih
**Lokasi:** `MonthlyTargetController::destroy()` (resource) vs route eksplisit `monthly-targets.destroy` → `TargetAssignmentController::destroyMonthly`.
**Masalah:** Resource route mendaftarkan `destroy` di `MonthlyTargetController`, tapi di-override route eksplisit ke controller lain. Kemungkinan `MonthlyTargetController::destroy` **dead/unreachable** — perlu dipastikan ia tidak menyediakan jalur hapus tanpa cek `authorizeMonthly`.
**Perbaikan:** Hapus method yang tak terpakai atau pastikan keduanya memanggil guard ownership yang sama.

---

## 🔵 LOW / cleanup

- **L1** — `layouts/navigation.blade.php`: dead code Breeze lama (emoji 👤🎯, tidak ter-render layout baru). Hapus.
- **L2** — Emoji pada flash message di controller (`'✅ Laporan…'`, `'↩ Laporan…'`, `'📨 Revisi…'`) tidak konsisten dengan keputusan "no-emoji"; pindah ke teks polos.
- **L3** — Halaman admin (`admin/kpi-settings`, `admin/target-assignment`) masih memakai emoji sebagai ikon (di luar scope UX sebelumnya).
- **L4** — `Schedule::command('inspire')`/`db:clean-dummy` di `routes/console.php` — pastikan command util tidak terekspos sembarangan di produksi.

---

## 🏛️ ARCH — Peluang deepening

### A1 — Dua sumbu status (`status` kerja vs `verification_status` review) tersebar ke banyak tempat
**Gejala:** Logika "boleh edit?", "boleh selesai?", "boleh review?", "auto-reject", "is_overdue" semuanya menggabungkan kedua sumbu, tersebar di controller (`authorizeEdit`, `complete`, `transitionReview`, `authorizeReview`) dan model (`canBeEdited`, `canBeRevised`, `autoRejectExpiredRevision`). Sulit dites end-to-end; mudah lupa satu cabang.
**Deletion test:** menggabungkan aturan transisi ke satu modul **memusatkan** kompleksitas (lulus). 
**Deepening:** buat satu modul **ReportLifecycle** (state machine) yang menjadi satu-satunya pemilik transisi `pending → revision → rejected/approved` dan invarian `selesai`/`approved`. Controller hanya memanggil `lifecycle->approve()`, `->requestRevision($note)`, dll. Interface tipis (beberapa method niat), implementasi dalam → "the interface is the test surface": tes lifecycle tanpa HTTP.

### A2 — `WeeklyTarget` query "yang relevan untuk user" diduplikasi
**Gejala:** Blok `where(...orWhere(whereNull('assigned_to')...))` yang sama persis ada di `create()` dan `edit()` (≈40 baris identik). Drift risiko bila aturan berubah.
**Deepening:** ekstrak ke scope query `WeeklyTarget::assignableTo($user)` — satu seam, dites sekali.

### A3 — Coupling notifikasi via `NotificationHelper` statis
**Gejala:** Transisi memanggil `NotificationHelper::reportApproved(...)` dll secara statis di dalam transaksi. Sulit di-mock; notifikasi terikat ke alur tulis.
**Deepening:** dispatch event domain (`ReportApproved`) + listener; transaksi hanya menulis state. Memisahkan "apa yang terjadi" dari "siapa yang diberi tahu".

---

## 🎨 UX / Frontend (ui-ux-pro-max)

Sudah diperbaiki di ronde sebelumnya (token tipografi 16px, kontras fg-3, ikon Lucide menggantikan ~250 emoji, kartu tugas terpisah + aksen status, afordansi klik, hijau=approved, touch target filter). Sisa temuan:

| # | Area | Temuan | Aturan | Rekomendasi |
|---|------|--------|--------|-------------|
| U1 | `backdate-requests/index` | Input search masih `width:250px` fixed → bisa overflow di mobile 360px | `horizontal-scroll`, `mobile-first` | Ubah ke `flex:1 1 220px;min-width:200px` (seperti daily-tasks). |
| U2 | Form (`daily-tasks/create`, target, kpi) | Validasi hanya saat submit; tidak ada inline-validation on blur | `inline-validation`, `error-placement` | Validasi on-blur + error di bawah field. |
| U3 | Aksi async (Setujui/Tolak/Submit) | Tombol tidak masuk state loading/disabled saat submit | `loading-buttons`, `submit-feedback` | Disable + spinner saat submit; cegah double-submit. |
| U4 | Konfirmasi destruktif | `confirm()` native dipakai untuk hapus/approve | `confirmation-dialogs` | OK fungsional; pertimbangkan sheet/modal in-app agar konsisten & tidak memblok. |
| U5 | Tabel data (backdate, kpi) | Tidak ada sort `aria-sort`, header non-interaktif | `sortable-table` | Tambah sort + indikator. |
| U6 | Empty/error state job AI | Saat AI tak kunjung selesai (lihat C1) tidak ada pesan "gagal/retry" | `error-state-chart`, `timeout-feedback` | Tampilkan status gagal + tombol "Nilai ulang" setelah worker aktif. |
| U7 | Aksesibilitas | Banyak ikon-only `icon-btn` tanpa `aria-label` di beberapa view | `aria-labels` | Audit semua tombol ikon, tambah label. |
| U8 | Admin pages | Masih emoji + font kecil (L3) | `no-emoji-icons` | Rol-out pola Lucide ke admin. |

---

## Urutan prioritas eksekusi

1. **C1 + C2** — perbaiki deployment (worker + scheduler + server produksi). Ini membuka fungsi AI yang saat ini mati total.
2. **C3 + C4** — hentikan reset password & akun dummy di produksi (keamanan).
3. **H1–H3** — ketahanan job AI + transaksi evidence.
4. **M1–M5** — konsistensi window revisi, pindahkan auto-reject ke scheduler, N+1.
5. **A1** — deepening ReportLifecycle (fondasi agar bug status tak terulang).
6. **U1–U8** — penyempurnaan UX & aksesibilitas; rol-out pola ke admin.

> Catatan: item C1–C4 bersifat **infrastruktur/deploy**, bukan logika aplikasi — kode aplikasinya sendiri relatif solid (locking transaksi pada approve/complete/backdate sudah benar, otorisasi target & review sudah cek departemen dengan tepat, tidak ada SQL injection / mass-assignment liar / debug leftover yang ditemukan).
