<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,booking_id',
            'payment_method' => 'required|in:transfer_bank,qris,e_wallet,kartu_kredit,tunai',
            'amount' => 'required|numeric',
        ]);

        $booking = Booking::find($request->booking_id);

        if ($booking->booking_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak berstatus pending.',
            ], 400);
        }

        // Simpan Payment
        // PENTING: Insert ini akan memicu 2 Trigger:
        // 1. trg_payments_before_insert (Validasi nominal)
        // 2. trg_payments_after_insert (Auto-update status booking jadi 'paid' & catat log)
        $payment = Payment::create([
            'booking_id' => $request->booking_id,
            'payment_method' => $request->payment_method,
            'amount' => $request->amount,
            'payment_status' => 'success',
            'transaction_ref' => strtoupper($request->payment_method) . '-' . time(),
            'paid_at' => now(),
        ]);

        // Refresh data booking untuk melihat perubahan status dari Trigger
        $booking->refresh(); 

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil. Status booking otomatis berubah menjadi PAID.',
            'data' => [
                'payment' => $payment,
                'updated_booking_status' => $booking->booking_status
            ]
        ], 201);
    }
}