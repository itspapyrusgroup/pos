<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KaryawanController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $roleId = $request->input('role_id');
        $divisiId = $request->input('divisi_id');
        $jabatanId = $request->input('jabatan_id');
        $cabangId = $request->input('cabang_id');
        $status = $request->input('status');

        $query = Karyawan::query()
            ->with(['user.role', 'divisi', 'jabatan', 'user.cabang'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('nama', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('username', 'like', '%' . $search . '%');
                      });
                });
            })
            ->when($roleId !== null && $roleId !== '', function ($q) use ($roleId) {
                $q->whereHas('user', function ($q) use ($roleId) {
                    $q->where('role_id', $roleId);
                });
            })
            ->when($divisiId !== null && $divisiId !== '', function ($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId);
            })
            ->when($jabatanId !== null && $jabatanId !== '', function ($q) use ($jabatanId) {
                $q->where('jabatan_id', $jabatanId);
            })
            ->when($cabangId !== null && $cabangId !== '', function ($q) use ($cabangId) {
                $q->whereHas('user.cabang', function ($inner) use ($cabangId) {
                    $inner->where('cabang.id', $cabangId);
                });
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('status', (int) $status);
            })
            ->orderByDesc('id');

        // Get unique roles, jabatan, and cabang for filter options
        $roles = Role::query()->where('status', true)->orderBy('nama')->get();
        $divisiList = Divisi::query()->where('status', true)->orderBy('nama')->get();
        $jabatanList = Jabatan::query()->where('status', true)->orderBy('nama')->get();
        $cabangList = Cabang::query()->where('status', true)->orderBy('nama')->get();

        return view('pages.master.karyawan.index', [
            'karyawan' => $query->paginate(15)->withQueryString(),
            'roles' => $roles,
            'divisiList' => $divisiList,
            'jabatanList' => $jabatanList,
            'cabangList' => $cabangList,
            'filters' => [
                'search' => $search,
                'role_id' => $roleId ?? '',
                'divisi_id' => $divisiId ?? '',
                'jabatan_id' => $jabatanId ?? '',
                'cabang_id' => $cabangId ?? '',
                'status' => $status ?? '',
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.master.karyawan.form', [
            'karyawan' => new Karyawan(),
            'roles' => Role::query()->where('status', true)->orderBy('nama')->get(),
            'divisi' => Divisi::query()->where('status', true)->orderBy('nama')->get(),
            'jabatan' => Jabatan::query()->where('status', true)->orderBy('nama')->get(),
            'cabang' => Cabang::query()->where('status', true)->orderBy('nama')->get(),
            'selectedCabang' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($validated, $request): void {
            $photoPath = null;
            if ($request->hasFile('foto_profil')) {
                $photoPath = $request->file('foto_profil')->store('profile', 'public');
            }

            $user = User::create([
                'name' => $validated['nama'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'no_wa' => $validated['no_wa'] ?? null,
                'role_id' => $validated['role_id'],
                'foto_profil' => $photoPath,
                'status' => (bool) ($validated['status'] ?? false),
            ]);

            $user->cabang()->sync($validated['cabang_ids']);

            $karyawan = Karyawan::create([
                'cabang_id' => $validated['cabang_ids'][0],
                'divisi_id' => $validated['divisi_id'] ?? null,
                'jabatan_id' => $validated['jabatan_id'] ?? null,
                'user_id' => $user->id,
                'nama' => $validated['nama'],
                'no_hp' => $validated['no_wa'] ?? null,
                'email' => $validated['email'],
                'status' => (bool) ($validated['status'] ?? false),
            ]);
        });

        return redirect()->route('konfigurasi.karyawan')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function edit(Karyawan $karyawan): View
    {
        $karyawan->load(['user', 'divisi', 'jabatan', 'user.cabang']);

        return view('pages.master.karyawan.form', [
            'karyawan' => $karyawan,
            'roles' => Role::query()->where('status', true)->orderBy('nama')->get(),
            'divisi' => Divisi::query()->where('status', true)->orderBy('nama')->get(),
            'jabatan' => Jabatan::query()->where('status', true)->orderBy('nama')->get(),
            'cabang' => Cabang::query()->where('status', true)->orderBy('nama')->get(),
            'selectedCabang' => $karyawan->user?->cabang?->pluck('id')->all() ?? [],
        ]);
    }

    public function update(Request $request, Karyawan $karyawan): RedirectResponse
    {
        $user = $karyawan->user;
        $validated = $this->validatePayload($request, $user?->id);

        DB::transaction(function () use ($validated, $request, $karyawan, $user): void {
            $payload = [
                'name' => $validated['nama'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'no_wa' => $validated['no_wa'] ?? null,
                'role_id' => $validated['role_id'],
                'status' => (bool) ($validated['status'] ?? false),
            ];

            if (!empty($validated['password'])) {
                $payload['password'] = Hash::make($validated['password']);
            }

            if ($request->hasFile('foto_profil')) {
                if ($user?->foto_profil) {
                    Storage::disk('public')->delete($user->foto_profil);
                }
                $payload['foto_profil'] = $request->file('foto_profil')->store('profile', 'public');
            }

            $user->update($payload);
            $user->cabang()->sync($validated['cabang_ids']);

            $karyawan->update([
                'cabang_id' => $validated['cabang_ids'][0],
                'divisi_id' => $validated['divisi_id'] ?? null,
                'jabatan_id' => $validated['jabatan_id'] ?? null,
                'nama' => $validated['nama'],
                'no_hp' => $validated['no_wa'] ?? null,
                'email' => $validated['email'],
                'status' => (bool) ($validated['status'] ?? false),
            ]);
        });

        return redirect()->route('konfigurasi.karyawan')->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Karyawan $karyawan): RedirectResponse
    {
        DB::transaction(function () use ($karyawan): void {
            $user = $karyawan->user;

            $karyawan->delete();

            if ($user) {
                $user->cabang()->detach();
                if ($user->foto_profil) {
                    Storage::disk('public')->delete($user->foto_profil);
                }
                $user->delete();
            }
        });

        return redirect()->route('konfigurasi.karyawan')->with('success', 'Karyawan berhasil dihapus.');
    }

    private function validatePayload(Request $request, ?int $userId = null): array
    {
        $passwordRule = $userId
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($userId)],
            'password' => $passwordRule,
            'no_wa' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'divisi_id' => ['nullable', 'exists:divisi,id'],
            'jabatan_id' => ['nullable', 'exists:jabatan,id'],
            'cabang_ids' => ['required', 'array', 'min:1'],
            'cabang_ids.*' => ['exists:cabang,id'],
            'foto_profil' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'boolean'],
        ]);
    }
}
