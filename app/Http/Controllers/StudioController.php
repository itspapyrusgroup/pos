<?php

namespace App\Http\Controllers;

use App\Models\Studio;
use App\Models\TemaStudio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StudioController extends Controller
{
    public function index(Request $request)
    {
        $query = Studio::query()->with(['cabang:id,kode,nama', 'temaStudio:id,nama']);
        $this->applyCabangScope($query, 'cabang_id');

        if ($request->filled('cabang_id')) {
            $cabangId = (int) $request->input('cabang_id');
            $this->ensureCabangAccessible($cabangId);
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . trim((string) $request->input('nama')) . '%');
        }

        if ($request->filled('tema_studio_id')) {
            $query->where('tema_studio_id', (int) $request->input('tema_studio_id'));
        }

        if ($request->has('status') && $request->input('status') !== '' && in_array((string) $request->input('status'), ['0', '1'], true)) {
            $query->where('status', (bool) ((int) $request->input('status')));
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 10)));
        $items = $query->orderBy('cabang_id')->orderBy('nama')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $items->getCollection()->map(fn (Studio $item) => $this->transformStudio($item))->values(),
            'total' => $items->total(),
            'current_page' => $items->currentPage(),
            'per_page' => $items->perPage(),
            'last_page' => $items->lastPage(),
        ]);
    }

    public function show(int $id)
    {
        $studio = Studio::query()->with(['cabang:id,kode,nama', 'temaStudio:id,nama'])->find($id);
        if (!$studio) {
            return response()->json(['message' => 'Studio tidak ditemukan'], 404);
        }

        $this->ensureCabangAccessible((int) $studio->cabang_id);

        return response()->json($this->transformStudio($studio));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cabang_id' => ['required', 'exists:cabang,id'],
            'tema_studio_id' => ['nullable', 'exists:tema_studio,id'],
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('studio')->where(function ($query) use ($request) {
                    return $query->where('cabang_id', $request->input('cabang_id'));
                }),
            ],
            'status' => ['required', 'boolean'],
        ], [
            'nama.unique' => 'Nama studio sudah digunakan di cabang ini.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        $studio = DB::transaction(function () use ($validated) {
            return Studio::query()->create($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Studio berhasil ditambahkan',
            'data' => $this->transformStudio($studio->load(['cabang:id,kode,nama', 'temaStudio:id,nama'])),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $studio = Studio::query()->find($id);
        if (!$studio) {
            return response()->json(['message' => 'Studio tidak ditemukan'], 404);
        }

        $this->ensureCabangAccessible((int) $studio->cabang_id);

        $validator = Validator::make($request->all(), [
            'cabang_id' => ['required', 'exists:cabang,id'],
            'tema_studio_id' => ['nullable', 'exists:tema_studio,id'],
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('studio')
                    ->ignore($studio->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('cabang_id', $request->input('cabang_id'));
                    }),
            ],
            'status' => ['required', 'boolean'],
        ], [
            'nama.unique' => 'Nama studio sudah digunakan di cabang ini.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        DB::transaction(function () use ($studio, $validated) {
            $studio->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Studio berhasil diperbarui',
            'data' => $this->transformStudio($studio->fresh(['cabang:id,kode,nama', 'temaStudio:id,nama'])),
        ]);
    }

    public function destroy(int $id)
    {
        $studio = Studio::query()->find($id);
        if (!$studio) {
            return response()->json(['message' => 'Studio tidak ditemukan'], 404);
        }

        $this->ensureCabangAccessible((int) $studio->cabang_id);
        $studio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Studio berhasil dihapus',
        ]);
    }

    public function getCabang()
    {
        $data = $this->accessibleCabangQuery()
            ->get(['id', 'kode', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getTemaStudio()
    {
        $data = TemaStudio::query()
            ->where('status', true)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function transformStudio(Studio $studio): array
    {
        return [
            'id' => $studio->id,
            'cabang_id' => $studio->cabang_id,
            'tema_studio_id' => $studio->tema_studio_id,
            'nama' => $studio->nama,
            'status' => (bool) $studio->status,
            'cabang' => $studio->cabang ? [
                'id' => $studio->cabang->id,
                'kode' => $studio->cabang->kode,
                'nama' => $studio->cabang->nama,
            ] : null,
            'tema_studio' => $studio->temaStudio ? [
                'id' => $studio->temaStudio->id,
                'nama' => $studio->temaStudio->nama,
            ] : null,
        ];
    }
}
