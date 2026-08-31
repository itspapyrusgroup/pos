<?php

namespace App\Http\Controllers;

use App\Models\MetodePembayaran;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanPaymentMethodLog;
use App\Models\PenjualanVoidOtp;
use App\Models\PesananPenjualan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanPaymentMethodController extends Controller
{
    public function update(Request $request, PesananPenjualan $pesananPenjualan, PembayaranPenjualan $pembayaranPenjualan): RedirectResponse
    {
        $data = $request->validate([
            'metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],
            'otp' => ['required', 'string', 'max:10'],
            'alasan' => ['required', 'string', 'min:5'],
        ]);

        $this->ensureCabangAccessible((int) $pesananPenjualan->cabang_id);

        DB::transaction(function () use ($data, $pesananPenjualan, $pembayaranPenjualan) {
            $order = PesananPenjualan::query()
                ->with('kantongOrder:id,pesanan_penjualan_id')
                ->lockForUpdate()
                ->findOrFail($pesananPenjualan->id);

            $payment = PembayaranPenjualan::query()
                ->with('metodePembayaran:id,nama')
                ->lockForUpdate()
                ->findOrFail($pembayaranPenjualan->id);

            if ((int) $payment->pesanan_penjualan_id !== (int) $order->id) {
                throw ValidationException::withMessages([
                    'metode_pembayaran_id' => ['Pembayaran tidak cocok dengan transaksi yang dipilih.'],
                ]);
            }

            if ((float) $payment->nominal <= 0 || (string) $payment->tipe === 'VOID') {
                throw ValidationException::withMessages([
                    'metode_pembayaran_id' => ['Hanya pembayaran aktif dengan nominal positif yang bisa diganti metodenya.'],
                ]);
            }

            $newMethod = MetodePembayaran::query()
                ->where('status', true)
                ->whereHas('cabang', function ($q) use ($order) {
                    $q->where('cabang.id', (int) $order->cabang_id);
                })
                ->find($data['metode_pembayaran_id']);

            if (!$newMethod) {
                throw ValidationException::withMessages([
                    'metode_pembayaran_id' => ['Metode pembayaran baru tidak aktif untuk cabang transaksi ini.'],
                ]);
            }

            if ((int) $newMethod->id === (int) $payment->metode_pembayaran_id) {
                throw ValidationException::withMessages([
                    'metode_pembayaran_id' => ['Metode pembayaran baru harus berbeda dari metode saat ini.'],
                ]);
            }

            $otp = PenjualanVoidOtp::query()
                ->where('kode_otp', strtoupper(trim((string) $data['otp'])))
                ->where('pesanan_penjualan_id', (int) $order->id)
                ->where('tipe_void', 'CHANGE_METHOD')
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                throw ValidationException::withMessages([
                    'otp' => ['OTP koreksi metode tidak valid untuk transaksi ini.'],
                ]);
            }
            if ($otp->used_at) {
                throw ValidationException::withMessages([
                    'otp' => ['OTP koreksi metode sudah pernah digunakan.'],
                ]);
            }
            if ($otp->expired_at && $otp->expired_at->isPast()) {
                throw ValidationException::withMessages([
                    'otp' => ['OTP koreksi metode sudah kedaluwarsa.'],
                ]);
            }

            $otpPaymentIds = collect($otp->item_payload ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values();

            if ($otpPaymentIds->isNotEmpty() && !$otpPaymentIds->contains((int) $payment->id)) {
                throw ValidationException::withMessages([
                    'otp' => ['OTP ini tidak dibuat untuk pembayaran yang dipilih.'],
                ]);
            }

            $correctedAt = now();
            $oldMethodId = (int) $payment->metode_pembayaran_id;

            PenjualanPaymentMethodLog::query()->create([
                'pesanan_penjualan_id' => (int) $order->id,
                'pembayaran_penjualan_id' => (int) $payment->id,
                'otp_id' => (int) $otp->id,
                'from_metode_pembayaran_id' => $oldMethodId,
                'to_metode_pembayaran_id' => (int) $newMethod->id,
                'nominal' => abs((float) $payment->nominal),
                'alasan' => trim((string) $data['alasan']),
                'corrected_at' => $correctedAt,
                'corrected_by_user_id' => (int) Auth::id(),
                'authorized_by_user_id' => $otp->generated_by_user_id ? (int) $otp->generated_by_user_id : null,
            ]);

            $payment->update([
                'metode_pembayaran_id' => (int) $newMethod->id,
                'updated_at' => $correctedAt,
            ]);

            $otp->update([
                'used_at' => $correctedAt,
                'used_by_user_id' => (int) Auth::id(),
            ]);
        });

        return back()->with('success', 'Metode pembayaran berhasil diperbarui.');
    }
}
