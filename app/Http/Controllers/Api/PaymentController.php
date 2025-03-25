<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // Supprimer un paiement
    public function deletePayment($id)
    {
        $deleted = DB::table('payments')
                    ->where('id', $id)
                    ->delete();
        
        if ($deleted) {
            return response()->json(['message' => 'Paiement supprimé']);
        }
        
        return response()->json(['message' => 'Paiement introuvable'], 404);
    }

    // Modifier un paiement
    public function updatePayment(Request $request, $id)
    {
        // Validation des données
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        try {
            // Mise à jour directe avec DB
            $updated = DB::table('payments')
                    ->where('id', $id)
                    ->update([
                        'amount' => $validated['amount'],
                        'payment_date' => $validated['payment_date'],
                        'updated_at' => now() // Mise à jour du timestamp
                    ]);

            if (!$updated) {
                return response()->json(['message' => 'Paiement introuvable'], 404);
            }

            // Récupération du paiement mis à jour
            $payment = DB::table('payments')->find($id);

            return response()->json([
                'message' => 'Paiement mis à jour',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
