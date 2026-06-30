<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * Daftar semua user dengan filter opsional by departemen / role.
     */
    public function index(Request $request)
    {
        $query = User::query()->where('role', '!=', 'super_admin');

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users       = $query->orderBy('department')->orderBy('name')->get();
        $departments = User::DEPARTMENTS;
        $roles       = collect(User::ROLES)->except('super_admin')->toArray();

        return view('admin.users.index', compact('users', 'departments', 'roles'));
    }

    /**
     * Form tambah user baru.
     */
    public function create()
    {
        $departments = User::DEPARTMENTS;
        $roles       = collect(User::ROLES)->except('super_admin')->toArray();

        return view('admin.users.create', compact('departments', 'roles'));
    }

    /**
     * Simpan user baru ke database.
     * Password OPSIONAL: kalau diisi, user bisa langsung login dengan email & password.
     * Kalau dikosongkan, user login via Google lalu set password sendiri (EnsurePasswordIsSet).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'role'       => 'required|in:staff,leader,c_level',
            'department' => 'nullable|string',
            'division'   => 'nullable|string|max:255',
            'password'   => 'nullable|string|min:8',
            'is_management' => 'boolean',
        ]);

        User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'role'          => $validated['role'],
            'department'    => $validated['department'] ?? null,
            'division'      => $validated['division'] ?? null,
            'is_management' => $request->boolean('is_management'),
            // Cast 'hashed' di model akan otomatis hash nilai plain ini.
            'password'      => ! empty($validated['password']) ? $validated['password'] : null,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Akun {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Form edit data user yang sudah ada.
     */
    public function edit(User $user)
    {
        $departments = User::DEPARTMENTS;
        $roles       = collect(User::ROLES)->except('super_admin')->toArray();

        return view('admin.users.edit', compact('user', 'departments', 'roles'));
    }

    /**
     * Update data user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'role'          => 'required|in:staff,leader,c_level',
            'department'    => 'nullable|string',
            'division'      => 'nullable|string|max:255',
            'password'      => 'nullable|string|min:8',
            'is_management' => 'boolean',
        ]);

        $data = [
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'role'          => $validated['role'],
            'department'    => $validated['department'] ?? null,
            'division'      => $validated['division'] ?? null,
            'is_management' => $request->boolean('is_management'),
        ];

        // Hanya ubah password jika admin mengisinya (cast 'hashed' meng-hash otomatis).
        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    /**
     * Toggle status aktif/nonaktif akun pengguna.
     * Menggunakan kolom is_active (soft disable — akun tidak dihapus dari DB).
     */
    public function toggleActive(User $user)
    {
        // Cegah super_admin menonaktifkan dirinya sendiri
        if ($user->role === 'super_admin') {
            return back()->with('error', 'Akun Super Admin tidak dapat dinonaktifkan.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->name} berhasil {$status}.");
    }
}
