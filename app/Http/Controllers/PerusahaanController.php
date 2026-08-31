<?php

namespace App\Http\Controllers;

use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PerusahaanController extends Controller
{
    public function index(Request $request)
    {
        $query = Perusahaan::query();

        // Filter by nama
        if ($request->has('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }

        // Filter by kode
        if ($request->has('kode')) {
            $query->where('kode', 'like', '%' . $request->kode . '%');
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['0', '1'])) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = min(100, max(1, (int) $request->input('per_page', 10)));
        $companies = $query->paginate($perPage);

        return response()->json([
            'data' => $companies->items(),
            'total' => $companies->total(),
            'current_page' => $companies->currentPage(),
            'per_page' => $companies->perPage(),
            'last_page' => $companies->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'npwp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'no_hp' => 'required|string|max:15',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();
        $data['kode'] = Perusahaan::generateKode();

        $perusahaan = Perusahaan::create($data);

        return response()->json([
            'message' => 'Perusahaan berhasil ditambahkan',
            'data' => $perusahaan
        ], 201);
    }

    public function show($id)
    {
        $perusahaan = Perusahaan::find($id);

        if (!$perusahaan) {
            return response()->json(['message' => 'Perusahaan tidak ditemukan'], 404);
        }

        return response()->json($perusahaan);
    }

    public function update(Request $request, $id)
    {
        $perusahaan = Perusahaan::find($id);

        if (!$perusahaan) {
            return response()->json(['message' => 'Perusahaan tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'npwp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'no_hp' => 'required|string|max:15',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $perusahaan->update($validator->validated());

        return response()->json([
            'message' => 'Perusahaan berhasil diperbarui',
            'data' => $perusahaan
        ]);
    }

    public function destroy($id)
    {
        $perusahaan = Perusahaan::find($id);

        if (!$perusahaan) {
            return response()->json(['message' => 'Perusahaan tidak ditemukan'], 404);
        }

        $masihPunyaCabang = DB::table('cabang')
            ->where('perusahaan_id', $perusahaan->id)
            ->exists();

        if ($masihPunyaCabang) {
            if ($perusahaan->status) {
                $perusahaan->update(['status' => false]);
                return response()->json(['message' => 'Perusahaan masih memiliki cabang, tidak bisa dihapus. Status diubah menjadi Non Aktif.']);
            }

            return response()->json(['message' => 'Perusahaan masih memiliki cabang dan tetap Non Aktif.']);
        }

        $perusahaan->delete();

        return response()->json(['message' => 'Perusahaan berhasil dihapus']);
    }

    public function generateKode()
    {
        return response()->json([
            'kode' => Perusahaan::generateKode()
        ]);
    }
}
