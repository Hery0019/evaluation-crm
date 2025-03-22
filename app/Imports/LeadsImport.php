<?php

namespace App\Imports;

use App\Models\Lead;
use Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;

class LeadsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Lead([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $row['title'],
            'description' => $row['description'],
            'status_id' => $row['status_id'],
            'user_assigned_id' => $row['user_assigned_id'],
            'user_created_id' => $row['user_created_id'],
            'client_id' => $row['client_id'],
            'deadline' => $row['deadline'],
            'invoice_id' => $row['invoice_id'],
            'result' => $row['result'],
        ]);
    }
}
