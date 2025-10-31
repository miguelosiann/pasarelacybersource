<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Show payment success page
     */
    public function success(Payment $payment)
    {
        // Verify payment belongs to current user
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Acceso no autorizado');
        }

        return view('pages.payment.success', compact('payment'));
    }

    /**
     * Show payment failed page
     */
    public function failed()
    {
        $error = session('error', 'El pago no pudo ser procesado.');
        
        return view('pages.payment.failed', compact('error'));
    }

    /**
     * Show payment history
     */
    public function history()
    {
        $payments = Payment::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.payment.history', compact('payments'));
    }

    /**
     * Show payment details
     */
    public function show(Payment $payment)
    {
        // Verify payment belongs to current user
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Acceso no autorizado');
        }

        return view('pages.payment.show', compact('payment'));
    }
}
