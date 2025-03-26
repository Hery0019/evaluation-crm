<?php

namespace App\Imports;

use App\Models\Offer;
use App\Models\Lead;
use App\Models\Product;
use App\Models\InvoiceLine;
use App\Enums\OfferStatus;
use Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;

class OffersImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Vérifier si le Lead existe
        $lead = Lead::find($row['lead_id']);
        if (!$lead) {
            return null; // Ne pas importer si le lead est introuvable
        }

        // Création de l'offre
        $offer = Offer::create([
            'status' => OfferStatus::inProgress()->getStatus(),
            'client_id' => $lead->client_id,
            'external_id' => Uuid::uuid4()->toString(),
            'source_id' => $lead->id,
            'source_type' => Lead::class,
        ]);

        // Vérification et ajout des lignes de facture
        if (!empty($row['title']) && !empty($row['type']) && !empty($row['price']) && !empty($row['quantity'])) {
            $invoiceLine = new InvoiceLine([
                'title' => $row['title'],
                'type' => $row['type'],
                'quantity' => $row['quantity'] ?: 1,
                'comment' => $row['comment'] ?? null,
                'price' => $row['price'] * 100, // Conversion en centimes
                'product_id' => $row['product'] ? Product::where('external_id', $row['product'])->value('id') : null,
            ]);
            $offer->invoiceLines()->save($invoiceLine);
        }
        return $offer;
    }
}
