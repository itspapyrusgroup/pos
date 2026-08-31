<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function accessibleCabangIds(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $ids = $user->cabang()->pluck('cabang.id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        return $ids;
    }

    protected function hasCabangRestriction(): bool
    {
        return count($this->accessibleCabangIds()) > 0;
    }

    protected function accessibleCabangQuery(): Builder
    {
        $ids = $this->accessibleCabangIds();

        return Cabang::query()
            ->where('status', true)
            ->when(!empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('nama');
    }

    protected function applyCabangScope(Builder $query, string $column = 'cabang_id'): Builder
    {
        $ids = $this->accessibleCabangIds();
        if (!empty($ids)) {
            $query->whereIn($column, $ids);
        }

        return $query;
    }

    protected function ensureCabangAccessible(?int $cabangId): void
    {
        if (!$cabangId) {
            return;
        }

        $ids = $this->accessibleCabangIds();
        if (!empty($ids) && !in_array($cabangId, $ids, true)) {
            throw ValidationException::withMessages([
                'cabang_id' => ['Anda tidak memiliki akses ke cabang ini.'],
            ]);
        }
    }

    protected function resolveCabangFilter(Request $request, string $key = 'cabang_id'): ?int
    {
        $requested = $request->filled($key) ? (int) $request->input($key) : null;
        $ids = $this->accessibleCabangIds();

        if (empty($ids)) {
            return $requested ?: $this->activeCabangId();
        }

        if ($requested && in_array($requested, $ids, true)) {
            return $requested;
        }

        $active = $this->activeCabangId();
        if ($active && in_array($active, $ids, true)) {
            return $active;
        }

        return $ids[0] ?? $active;
    }

    protected function resolveCabangFilters(Request $request, string $key = 'cabang_id'): array
    {
        $requested = collect((array) $request->input($key, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $accessibleIds = $this->accessibleCabangIds();
        if (empty($accessibleIds)) {
            return $requested;
        }

        if (!empty($requested)) {
            return array_values(array_intersect($requested, $accessibleIds));
        }

        return $accessibleIds;
    }

    protected function activeCabangId(): ?int
    {
        $ids = $this->accessibleCabangIds();
        $active = (int) session('active_cabang_id');

        if (!empty($ids)) {
            if ($active && in_array($active, $ids, true)) {
                return $active;
            }

            $active = $ids[0] ?? null;
            if ($active) {
                session(['active_cabang_id' => $active]);
            }

            return $active;
        }

        if ($active && Cabang::query()->where('id', $active)->where('status', true)->exists()) {
            return $active;
        }

        $first = $this->accessibleCabangQuery()->value('id');
        if ($first) {
            session(['active_cabang_id' => (int) $first]);
        }

        return $first ? (int) $first : null;
    }
}
