<?php

use  Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Client;

class ClientsImport implements ToModel, WithHeadingRow, WithCustomCsvSettings
{
    public function model(array $row)
    {
        return new Client([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $row['name'],
            'vat' => $row['vat'],
            'company_name' => $row['company_name'],
            'email' => $row['email'],
            'address' => $row['address'],
            'zipcode' => $row['zipcode'],
            'city' => $row['city'],
            'primary_number' => $row['primary_number'],
            'secondary_number' => $row['secondary_number'],
            'industry_id' => $row['industry_id'],
            'company_type' => $row['company_type'],
            'user_id' => $row['user_id'],
            'client_number' => $row['client_number'],
        ]);
    }
}
