<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleUserController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->with('permissions:id,kode,label,modul')
            ->orderBy('nama')
            ->get();

        return view('pages.master.role_user.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        return view('pages.master.role_user.form', [
            'role' => new Role(),
            'permissionsByModule' => Permission::query()
                ->orderBy('modul')
                ->orderBy('aksi')
                ->get()
                ->groupBy('modul'),
            'selectedPermissions' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:roles,nama'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('konfigurasi.role-karyawan')->with('success', 'Role user berhasil dibuat.');
    }

    public function edit(Role $role): View
    {
        return view('pages.master.role_user.form', [
            'role' => $role->load('permissions:id'),
            'permissionsByModule' => Permission::query()
                ->orderBy('modul')
                ->orderBy('aksi')
                ->get()
                ->groupBy('modul'),
            'selectedPermissions' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:roles,nama,' . $role->id],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('konfigurasi.role-karyawan')->with('success', 'Role user berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Role masih dipakai oleh karyawan dan tidak bisa dihapus.');
        }

        $role->delete();

        return redirect()->route('konfigurasi.role-karyawan')->with('success', 'Role user berhasil dihapus.');
    }
}
