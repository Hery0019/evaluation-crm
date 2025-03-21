<?php

namespace App\Imports;

use  Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Client;

class ClientsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Client([
            'external_id' => Uuid::uuid4()->toString(),
            'vat' => $row['vat'],
            'company_name' => $row['company_name'],
            'address' => $row['address'],
            'zipcode' => $row['zipcode'],
            'city' => $row['city'],
            'industry_id' => $row['industry_id'],
            'company_type' => $row['company_type'],
            'user_id' => $row['user_id'],
            'client_number' => $row['client_number'],
        ]);
    }
}
