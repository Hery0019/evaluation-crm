<?php

namespace App\Imports;

use Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Client;
use App\Models\Contact;

class ClientsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Création du client
        $client = new Client([
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

        // Sauvegarde du client pour obtenir son ID
        $client->save();

        // Création et insertion du contact lié au client
        $contact = new Contact([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $row['name'] ?? 'N/A',
            'email' => $row['email'] ?? 'N/A',
            'client_id' => $row['client_id'] ?? 'N/A',
            'primary_number' => $row['primary_number'] ?? 'N/A',
            'secondary_number' => $row['secondary_number'] ?? 'N/A',
        ]);

        $contact->save();

        // Retourner uniquement le client car ToModel ne supporte qu'un seul retour
        return $client;
    }
}
