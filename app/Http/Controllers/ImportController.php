<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProjectsImport;
use App\Imports\TasksImport;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    public function importCsv(Request $request)
    {
        try {
            // Validation des fichiers
            $request->validate([
                'projects_csv' => 'required|file|mimes:csv,txt|max:2048',
                'tasks_csv' => 'required|file|mimes:csv,txt|max:2048'
            ], [
                'projects_csv.required' => 'Le fichier projets est requis',
                'tasks_csv.required' => 'Le fichier tâches est requis',
                'mimes' => 'Seuls les fichiers CSV sont autorisés',
                'max' => 'La taille maximale des fichiers est de 2MB'
            ]);

            // Importation des fichiers
            $projectsCount = Excel::import(new ProjectsImport, $request->file('projects_csv'));
            $tasksCount = Excel::import(new TasksImport, $request->file('tasks_csv'));

            // Message de succès avec statistiques
            $successMessage = sprintf(
                "Importation réussie ! %d projets et %d tâches ont été importés.",
                $projectsCount->getRowCount(),
                $tasksCount->getRowCount()
            );

            return back()
                   ->with('success', $successMessage)
                   ->with('import_stats', [
                       'projects' => $projectsCount->getRowCount(),
                       'tasks' => $tasksCount->getRowCount()
                   ]);

        } catch (ValidationException $e) {
            // Erreurs de validation
            return back()
                   ->withErrors($e->validator)
                   ->withInput()
                   ->with('error', 'Erreur de validation des fichiers');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Erreurs de validation dans les fichiers Excel
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = sprintf(
                    "Ligne %d (%s): %s",
                    $failure->row(),
                    $failure->attribute(),
                    implode(', ', $failure->errors())
                );
            }

            return back()
                   ->with('error', 'Erreurs dans le fichier CSV')
                   ->with('error_details', $errorMessages);

        } catch (\Exception $e) {
            // Toutes les autres exceptions
            logger()->error('Erreur import CSV: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                   ->with('error', 'Une erreur est survenue lors de l\'importation')
                   ->with('error_details', $e->getMessage());
        }
    }
}