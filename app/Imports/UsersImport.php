<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;

class UsersImport implements ToModel
{
    public function model(array $row)
    {
        return new User([
            'name' => $row[0],
            'email' => $row[1],
            'password' => bcrypt($row[2]), // Adapte les colonnes selon ton CSV
        ]);
    }
}
