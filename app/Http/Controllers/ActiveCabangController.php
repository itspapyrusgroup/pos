<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveCabangController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_cabang_id' => ['required', 'exists:cabang,id'],
        ]);

        $cabangId = (int) $validated['active_cabang_id'];
        $allowed = $this->accessibleCabangIds();

        if (!empty($allowed) && !in_array($cabangId, $allowed, true)) {
            return back()->with('error', 'Cabang yang dipilih tidak termasuk akses Anda.');
        }

        $exists = Cabang::query()->where('id', $cabangId)->where('status', true)->exists();
        if (!$exists) {
            return back()->with('error', 'Cabang tidak aktif atau tidak ditemukan.');
        }

        session(['active_cabang_id' => $cabangId]);

        return back()->with('success', 'Cabang aktif berhasil diubah.');
    }
}
