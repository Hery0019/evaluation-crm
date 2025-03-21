<?php

namespace App\Imports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\ToModel;

class ProjectsImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Project([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $row['title'],
            'description' => $row['description'],
            'status_id' => $row['status_id'],
            'user_assigned_id' => $row['user_assigned_id'],
            'user_created_id' => $row['user_created_id'],
            'industry_id' => $row['industry_id'],
            'client_id' => $row['client_id'],
            'deadline' => $row['deadline'],
        ]);
    }
}
