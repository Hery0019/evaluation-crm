<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemiseController extends Controller
{
    /**
     * Met à jour le pourcentage de remise.
     *
     * @param Request $request
     * @return JsonResponse
     */
    
    public function updatePourcent(Request $request): JsonResponse
    {
        // Valider la requête et extraire la valeur de "remise"
        $request->validate([
            'remise' => 'required|numeric|min:0|max:100', // Assurez-vous que la remise est un nombre entre 0 et 100
        ]);

        $remise = $request->input('remise');

        // Mettre à jour le pourcentage de remise dans la base de données
        $updated = DB::update("UPDATE remise SET pourcentage = ? WHERE id = 1", [$remise]);

        // Vérifier si la mise à jour a réussi
        if ($updated) {
            return response()->json([
                'message' => 'Remise mise à jour avec succès',
                'pourcentage' => $remise,
            ]);
        } else {
            return response()->json([
                'message' => 'Aucune mise à jour effectuée',
            ], 400);
        }
    }
}