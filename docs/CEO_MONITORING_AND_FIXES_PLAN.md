# Planning — CEO Monitoring, Fix Bug "Tandai Selesai", & Rapikan Backend

> Dibuat: 2026-06-25 · Cabang: `fix-ghufron`
> Mode kerja: **full-stack** (aturan lama "backend-only" sudah dicabut).

## Keputusan yang dikunci (hasil diskusi)

| Topik | Keputusan |
|---|---|
| Scope | Full-stack (backend + Blade). |
| Aturan target CEO | **CEO → Leader saja**. Tidak ada penurunan target formal CEO→staff di sistem. CEO tetap bisa memantau progress staff. |
| Data lama | Update seeder agar mencerminkan flow baru **+ migrasi ulang** data CEO→staff yang terlanjur ada. |
| Bug "Tandai Selesai" | Laporan `approved` **tetap boleh** ditandai `selesai` (status kerja & verifikasi laporan = dua sumbu terpisah). |
| Tampilan CEO | **Dashboard ringkas** (kartu summary + progress per-dept + "staf perlu perhatian") yang nyambung ke drill-down period yang sudah ada. |
| Pemetaan migrasi | Target lama CEO→staff → di-remap ke **leader pertama departemen** (prioritas `is_management`, lalu id terkecil). Dept tanpa leader → `assigned_to = null`. |

---

## Phase 1 — Fix Bug "Tandai Selesai" (Staff) ✅ SELESAI

**Akar masalah:** `DailyTaskEntryController@complete` keluar lebih dulu bila `verification_status ∈ {approved, rejected}`, padahal tombol "Tandai Selesai" muncul selama `status !== 'selesai'`. Laporan yang sudah di-ACC tapi `status` masih `dalam_proses` → klik tombol → tidak ada perubahan.

**Perubahan:**
- `app/Http/Controllers/DailyTaskEntryController.php` (`complete()`):
  - Hanya `rejected` yang diblok; `approved` boleh diselesaikan.
  - Update hanya kolom `status`, **tidak** menyentuh `verification_status`.
  - `approved` tanpa catatan → selesai langsung (form edit memang terkunci).
  - Update atomik (`DB::transaction` + `lockForUpdate` + re-cek) → aman dari double-submit/balapan.
- `tests/Feature/DailyTask/DailyTaskGuardsTest.php`: test lama (yang menegaskan bug) diganti + tambah skenario approved→selesai, approved tanpa catatan, rejected diblok, redirect ke edit bila belum diverifikasi & tanpa catatan, non-owner 403.

**Hasil:** lulus.

---

## Phase 2 — CEO restriction + Seeder + Migrasi ✅ SELESAI

**2a. Batasi CEO assign hanya ke Leader**
- `MonthlyTargetController@create`: untuk eksekutif, daftar assignable = **role `leader`** (bukan `staff`).
- `MonthlyTargetController@store`: validasi server-side — bila pembuat C-Level & `assigned_to` diisi, wajib user ber-`role = leader`, jika tidak → error `assigned_to`.
- `resources/views/monthly-targets/create.blade.php`: label dinamis "Target untuk Leader/Staf" sesuai role.
- **[Koreksi]** `WeeklyTargetController` (jalur tugas granular yang juga bisa diakses CEO): `create()`/`edit()` daftar assignee dibatasi via `applyAssigneeRoleFilter` (leader→staff, **c_level→leader**, super_admin→staff+leader); `store()`/`update()` validasi `rejectIfClevelAssignsNonLeader`. Test: `tests/Feature/WeeklyTarget/AssignmentRestrictionTest.php`.

**2b. Seeder (cover flow baru)**
- `database/seeders/UserSeeder.php`: **1 leader per departemen** (operational/sales/marketing/product_it/hr/finance/ga/creative/customer_support) supaya CEO bisa assign ke leader di dept manapun.
- `database/seeders/UseCaseSeeder.php`: `seedCeoToLeaderTargets()` — monthly target dibuat C-Level, `assigned_to = leader`, beserta weekly + daily task milik leader (mengisi halaman `leader-targets`).

**2c. Migrasi data legacy**
- `database/migrations/2026_06_25_102636_remap_clevel_staff_targets_to_leader.php`: remap monthly target C-Level→staff ke leader dept (idempoten; no-op saat `migrate:fresh` karena jalan sebelum seeder).

**Tests:**
- `tests/Feature/MonthlyTarget/MonthlyTargetCrudTest.php`: CEO tak boleh assign ke staff; CEO boleh assign ke leader.
- `tests/Feature/MonthlyTarget/RemapClevelTargetsTest.php`: remap ke leader dept; dept tanpa leader → null; target buatan leader tak tersentuh.

**Hasil:** full suite **187/187 lulus**; `migrate:fresh --seed` sukses (flow CEO→Leader terisi).

---

## Phase 3 — CEO Dashboard Monitoring ✅ SELESAI

- Route `/ceo/overview` (`name: ceo.overview`), middleware `role:c_level`.
- `MonthlyTargetController@index`: C-Level di-redirect ke `ceo.overview` (pengganti page target lama).
- `app/Http/Controllers/CeoOverviewController.php`: kartu (total staf, rata-rata progres, menunggu review), agregasi progress per-departemen, daftar "staf perlu perhatian" (progress < 50%), filter periode bulan/tahun.
- `resources/views/ceo/overview.blade.php`: mockup "dashboard ringkas + drill"; kartu dept → `period.staff-list`, staf → `period.staff-targets`; tombol "＋ Target untuk Leader" → `monthly-targets.create`.
- `tests/Feature/Ceo/CeoOverviewTest.php`: authz (c_level ok, staff/leader 403), redirect index C-Level, render staf low-progress.

**Hasil:** full suite **192/192 lulus**.

---

## Phase 4 — Rapikan Backend ✅ SEBAGIAN (sisa perlu konfirmasi)

Sudah dikerjakan (aman):
- `DailyTaskEntryController`: hapus variabel mati `$isSales` di `store()` & `update()`.
- `DailyTaskEntryController`: relokasi docblock "Guard untuk review" yang salah tempat (dari atas `transitionReview` → ke atas `authorizeReview`).
- Bonus seeder: `UserSeeder` normalisasi department label → key valid (`'Product / IT'` → `product_it`; dept kosong → `null`).

Hasil: full suite **192/192 lulus**.

Ditahan, perlu konfirmasi user:
- Route `/deploy-update`, `/debug/run-migration`, `/debug/logs`, `/debug/unassigned-targets` (terkait deployment).
- Kolom legacy `proof_url` / `proof_file` di `daily_task_entries` (AI sudah baca evidence link).

---

## Catatan teknis
- DB test: SQLite in-memory + `RefreshDatabase`.
- Migration `2026_05_11_140000_seed_default_users.php` menanam user default tiap migrate (termasuk leader sales/marketing/product_it/operational) — relevan saat menulis test yang bergantung pada leader dept.
