<?php

namespace App\Imports;

use App\Models\Product;
use Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Vérifier si les données essentielles sont présentes
        if (empty($row['title']) || empty($row['price'])) {
            return null; // Ignorer la ligne si les champs obligatoires sont absents
        }

        return new Product([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $row['title'],
            'description' => $row['description'] ?? 'Description non fournie',
            'price' => $row['price'] * 100, // Conversion en centimes
            'number' => 10000,
             default_type // Valeur par défaut = 1
        ]);
    }
}
