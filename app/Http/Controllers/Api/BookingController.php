<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['visitor', 'items.ticketType'])->get();
        return response()->json(['success' => true, 'data' => $bookings], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'visitor_id' => 'required|exists:visitors,visitor_id',
            'visit_date' => 'required|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.ticket_type_id' => 'required|exists:ticket_types,ticket_type_id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Gunakan Transaksi Database
        return DB::transaction(function () use ($request) {
            $totalAmount = 0;
            
            // Hitung total manual untuk disimpan di header (sebelum trigger payment)
            foreach ($request->items as $item) {
                $price = \App\Models\TicketType::find($item['ticket_type_id'])->price;
                $totalAmount += ($price * $item['quantity']);
            }

            $booking = Booking::create([
                'booking_code' => 'WLT-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'visitor_id' => $request->visitor_id,
                'visit_date' => $request->visit_date,
                'total_amount' => $totalAmount,
                'booking_status' => 'pending',
                'expires_at' => now()->addDays(1),
            ]);

            // Simpan items (Trigger 'trg_bookings_after_insert' akan mencatat ke activity_log)
            foreach ($request->items as $item) {
                $price = \App\Models\TicketType::find($item['ticket_type_id'])->price;
                BookingItem::create([
                    'booking_id' => $booking->booking_id,
                    'ticket_type_id' => $item['ticket_type_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    // subtotal otomatis dihitung database (Generated Column)
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dibuat. Silakan lakukan pembayaran.',
                'data' => $booking->load('items.ticketType')
            ], 201);
        });
    }
}