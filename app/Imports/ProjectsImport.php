<?php

namespace App\Imports;

use Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Project;
use App\Models\Client;
use App\Models\Contact;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class ProjectsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Vérifier si le contact existe déjà
        $existingContact = Contact::where('name', $row['client_name'])->first();

        if ($existingContact) {
            // Si le contact existe, créer seulement le projet lié à ce client existant
            return Project::create([
                'external_id' => Uuid::uuid4()->toString(),
                'title' => $row['project_title'],
                'description' => Str::random(50),
                'status_id' => rand(11, 15),
                'client_id' => $existingContact->client_id,
                'invoice_id' => 1,
                'deadline' => Carbon::now()->addMonth(),
                'user_assigned_id' => 1,
                'user_created_id' => 1,
            ]);
        }

        // Si le contact n'existe pas, créer client + contact + projet
        $vat = 'FR' . rand(100000000, 999999999);
        $companyName = 'Entreprise ' . Str::random(5);
        $address = rand(1, 999) . ' Rue ' . Str::random(6);
        $zipcode = rand(10000, 99999);
        $city = 'Ville-' . Str::random(4);
        $industryId = rand(1, 10);
        $companyType = ['SARL', 'SA', 'Auto-Entrepreneur'][array_rand(['SARL', 'SA', 'Auto-Entrepreneur'])];
        $clientNumber = 'C' . rand(1000, 9999);
        $email = strtolower(Str::random(6)) . '@example.com';
        $primaryNumber = '+33' . rand(600000000, 699999999);
        $secondaryNumber = '+33' . rand(600000000, 699999999);

        $client = Client::create([
            'external_id' => Uuid::uuid4()->toString(),
            'vat' => $vat,
            'company_name' => $companyName,
            'address' => $address,
            'zipcode' => $zipcode,
            'city' => $city,
            'industry_id' => $industryId,
            'company_type' => $companyType,
            'user_id' => 1,
            'client_number' => $clientNumber,
        ]);

        $contact = Contact::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $row['client_name'],
            'email' => $email,
            'client_id' => $client->id,
            'primary_number' => $primaryNumber,
            'secondary_number' => $secondaryNumber,
        ]);

        return Project::create([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $row['project_title'],
            'description' => Str::random(50),
            'status_id' => rand(11, 15),
            'client_id' => $client->id,
            'invoice_id' => 1,
            'deadline' => Carbon::now()->addMonth(),
            'user_assigned_id' => 1,
            'user_created_id' => 1,
        ]);
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'client_name' => 'required|string|max:255',
            'project_title' => 'required|string|max:255',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public function customValidationMessages()
    {
        return [
            'client_name.required' => 'Le nom du client est requis',
            'project_title.required' => 'Le titre du projet est requis',
        ];
    }
}