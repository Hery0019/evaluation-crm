<?php

namespace App\Imports;

use App\Models\Task;
use App\Models\Project;
use Ramsey\Uuid\Uuid;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;

class TasksImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Récupérer le projet correspondant au titre donné
        $project = Project::where('title', $row['project_title'])->first();

        // Vérifier si le projet existe
        if (!$project) {
            return null; // Ne rien faire si aucun projet trouvé
        }

        // Récupérer l'ID du client et l'ID de l'utilisateur associé au client
        $clientId = $project->client_id;
        $userId = $project->client->user_id ?? 1; // Si aucun user_id, prendre 1 par défaut

        // Définir une deadline avant celle du projet
        $deadline = Carbon::parse($project->deadline)->subDays(rand(1, 15));

        // Création de la tâche associée
        return new Task([
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $row['task_title'],
            'description' => $row['description'] ?? 'Description auto-générée',
            'status_id' => rand(1, 6), // Status ID entre 1 et 6
            'user_assigned_id' => $userId, // Utilisateur du client du projet
            'user_created_id' => $userId, // Créateur = utilisateur du client du projet
            'client_id' => $clientId, // Client lié au projet
            'deadline' => $deadline, // Deadline avant celle du projet
            'project_id' => $project->id, // Lien avec le projet trouvé
        ]);
    }
}
