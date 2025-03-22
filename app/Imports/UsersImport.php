<?php

namespace App\Imports;

use Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new User([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => $row['password'],
            'address' => $row['address'],
            'primary_number' => $row['primary_number'],
            'secondary_number' => $row['secondary_number'],
            'image_path' => $row['image_path'],
            'language' => $row['language'],
        ]);
    }
}
