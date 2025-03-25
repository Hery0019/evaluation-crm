<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Payment;
use Carbon\Carbon;

class LeadController extends Controller
{
    public function getLeadsByMonthAndYear($month, $year)
    {
        // Récupérer les leads du mois sélectionné
        $leads = Lead::whereMonth('deadline', $month)
        ->whereYear('deadline', $year)
        ->get();

        // Récupérer les paiements liés aux leads
        $leads->each(function ($lead) {
            $lead->payments = Payment::whereHas('invoice', function ($query) use ($lead) {
                $query->where('source_id', $lead->id);
            })->get();
        });

        return response()->json($leads);
    }
}
