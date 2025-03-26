<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\FilesystemIntegration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use Ramsey\Uuid\Uuid;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Offer;
use App\Enums\OfferStatus;
use App\Models\InvoiceLine;
use App\Models\Invoice;
use App\Services\ClientNumber\ClientNumberService;
use \App\Services\InvoiceNumber\InvoiceNumberService;
use App\Enums\InvoiceStatus;

class DatabaseController extends Controller
{
    /**
     * Affiche le formulaire de suppression de données
     */
    public function deleteForm()
    {
        // Récupérer la liste des tables disponibles pour la suppression
        $tables = $this->getAvailableTables();
        
        return view('database.delete', compact('tables'));
    }

    /**
     * Traite la suppression des données
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'confirm' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Tables à ne pas vider
            $protectedTables = [
                'business_hours',
                'industries',
                'migrations', 
                'permission_role',
                'permissions',
                'roles',
                'settings',
                'statuses'
            ];

            // Tables avec première ligne à conserver
            $preserveFirstRowTables = [
                'departments',
                'department_user',
                'users'
            ];

            // Tables spéciales avec première association à conserver
            $specialTables = [                
                'role_user' => ['user_id', 1]
            ];

            // Désactiver les contraintes de clés étrangères
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Récupérer toutes les tables
            $tables = DB::select('SHOW TABLES');
            $dbName = 'Tables_in_' . env('DB_DATABASE');

            foreach ($tables as $table) {
                $tableName = $table->$dbName;

                if (!in_array($tableName, $protectedTables)) {
                    if (in_array($tableName, $preserveFirstRowTables)) {
                        // Supprimer toutes les lignes sauf la première
                        DB::table($tableName)
                            ->where('id', '>', 1)
                            ->delete();
                    } elseif (array_key_exists($tableName, $specialTables)) {
                        // Pour les tables avec des clés composites
                        list($field, $value) = $specialTables[$tableName];
                        DB::table($tableName)
                            ->where($field, '!=', $value)
                            ->delete();
                    } else {
                        // Vider complètement la table
                        DB::table($tableName)->delete();
                    }
                }
            }

            // Réactiver les contraintes de clés étrangères
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            return redirect()->back()
                ->with('flash_message', 'Les données ont été supprimées avec succès en préservant les tables et lignes spécifiées.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('flash_message_warning', 'Erreur lors de la suppression des données : ' . $e->getMessage());
        }
    }

    /**
     * Affiche le formulaire d'importation CSV
     */
    public function importForm()
    {
        // Récupérer la liste des tables disponibles pour l'importation
        $tables = $this->getAvailableTables();
        
        return view('database.import', compact('tables'));
    }

    /**
     * Récupère un client existant ou en crée un nouveau basé sur le nom du contact
     * 
     * @param string $contactName Le nom du contact
     * @return \App\Models\Client Le client récupéré ou créé
     */
    private function getOrCreateClient($contactName)
    {
        // Vérifier si le contact existe déjà
        $contact = Contact::where('name', $contactName)->first();
        
        if ($contact) {
            // Si le contact existe, utiliser son client
            return Client::find($contact->client_id);
        } else {
            // Si le contact n'existe pas, créer un nouveau client et contact
            $companyName = $contactName . ' Company'; // Nom d'entreprise par défaut basé sur le nom du contact
            
            // Créer le client
            $client = Client::create([
                'external_id' => Uuid::uuid4()->toString(),
                'vat' => '',
                'company_name' => $companyName,
                'address' => '',
                'zipcode' => '',
                'city' => '',
                'company_type' => '',
                'user_id' => 1, // Admin
                'industry_id' => 1, // Première industrie
                'client_number' => app(ClientNumberService::class)->setNextClientNumber(),
            ]);
            
            // Créer le contact associé au client
            Contact::create([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => $contactName,
                'email' => strtolower(str_replace(' ', '', $contactName)) . '@test.com',
                'primary_number' => '',
                'secondary_number' => '',
                'client_id' => $client->id,
                'is_primary' => true
            ]);
            
            return $client;
        }
    }

    /**
     * Traite l'importation CSV
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file1' => 'required|file|mimes:csv,txt',
            'csv_file2' => 'required|file|mimes:csv,txt',
            'csv_file3' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Démarrer une transaction
            DB::beginTransaction();
            
            // Traiter le premier fichier CSV (projets et clients)
            if ($request->hasFile('csv_file1')) {
                $file = $request->file('csv_file1');
            $path = $file->getRealPath();
            
            // Lire le fichier CSV
            $data = array_map('str_getcsv', file($path));
            
            // Récupérer les en-têtes (première ligne)
            $headers = array_shift($data);
            
                // Vérifier que les colonnes requises existent
                if (!in_array('project_title', $headers) || !in_array('client_name', $headers)) {
                    throw new \Exception('The CSV file must contain the columns "project_title" and "client_name"');
                }
                
                // Récupérer les index des colonnes
                $projectTitleIndex = array_search('project_title', $headers);
                $clientNameIndex = array_search('client_name', $headers);
                
                // Traiter chaque ligne
                foreach ($data as $row) {
                    $projectTitle = $row[$projectTitleIndex];
                    $contactName = $row[$clientNameIndex];
                    
                    // Récupérer ou créer le client
                    $client = $this->getOrCreateClient($contactName);
                    
                    // Créer le projet
                    Project::create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'title' => $projectTitle,
                        'description' => clean($projectTitle),
                        'status_id' => 11, //Project open
                        'user_assigned_id' => 1, // Admin
                        'user_created_id' => 1, // Admin
                        'client_id' => $client->id,
                        'deadline' => now()->addDays(3), // 3 jours après la date actuelle
                    ]);                    
                }
            }
            
            // Traiter le deuxième fichier CSV (tâches)
            if ($request->hasFile('csv_file2')) {
                $file = $request->file('csv_file2');
                $path = $file->getRealPath();
                
                // Lire le fichier CSV
                $data = array_map('str_getcsv', file($path));
                
                // Récupérer les en-têtes (première ligne)
                $headers = array_shift($data);
                
                // Vérifier que les colonnes requises existent
                if (!in_array('project_title', $headers) || !in_array('task_title', $headers)) {
                    throw new \Exception('The CSV file must contain the columns "project_title" and "task_title"');
                }
                
                // Récupérer les index des colonnes
                $projectTitleIndex = array_search('project_title', $headers);
                $taskTitleIndex = array_search('task_title', $headers);
                
                // Traiter chaque ligne
                foreach ($data as $row) {
                    $projectTitle = $row[$projectTitleIndex];
                    $taskTitle = $row[$taskTitleIndex];
                    
                    // Trouver le projet correspondant
                    $project = Project::where('title', $projectTitle)->first();
                    
                    if (!$project) {
                        throw new \Exception("Project '$projectTitle' does not exist");
                    }
                    
                    // Récupérer le client du projet
                    $client = Client::find($project->client_id);
                    
                    if (!$client) {
                        throw new \Exception("Client not found for project '$projectTitle'");
                    }
                                                                                
                    // Créer la tâche
                    Task::create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'title' => $taskTitle,
                        'description' => clean($taskTitle . ' description'),
                        'status_id' => 1, // Statut par défaut pour les tâches
                        'user_assigned_id' => 1, // Admin
                        'user_created_id' => 1, // Admin
                        'client_id' => $client->id,
                        'project_id' => $project->id,
                        'deadline' => now()->addDays(3), // 3 jours après la date actuelle
                    ]);
                }
            }
            
            // Traiter le troisième fichier CSV (offres et factures)
            if ($request->hasFile('csv_file3')) {
                $file = $request->file('csv_file3');
                $path = $file->getRealPath();
                
                // Lire le fichier CSV
                $data = array_map('str_getcsv', file($path));
                
                // Récupérer les en-têtes (première ligne)
                $headers = array_shift($data);
                
                // Vérifier que les colonnes requises existent
                $requiredColumns = ['client_name', 'lead_title', 'type', 'produit', 'prix', 'quantite'];
                foreach ($requiredColumns as $column) {
                    if (!in_array($column, $headers)) {
                        throw new \Exception("The CSV file must contain the column \"$column\"");
                    }
                }
                
                // Récupérer les index des colonnes
                $clientNameIndex = array_search('client_name', $headers);
                $leadTitleIndex = array_search('lead_title', $headers);
                $typeIndex = array_search('type', $headers);
                $produitIndex = array_search('produit', $headers);
                $prixIndex = array_search('prix', $headers);
                $quantiteIndex = array_search('quantite', $headers);                
                
                // Traiter chaque ligne individuellement
                foreach ($data as $index => $row) {
                    $clientName = $row[$clientNameIndex];
                    $leadTitle = $row[$leadTitleIndex];
                    $type = $row[$typeIndex];
                    $produit = $row[$produitIndex];
                    $prix = floatval($row[$prixIndex]);
                    $quantite = floatval($row[$quantiteIndex]);
                    
                    // Vérifier si le prix ou la quantité est négatif
                    if ($prix < 0 || $quantite < 0) {
                        $lineNumber = $index + 2; // +2 car l'index commence à 0 et on a enlevé la ligne d'en-tête
                        throw new \Exception("Erreur à la ligne $lineNumber : Les valeurs négatives ne sont pas autorisées (Prix: $prix, Quantité: $quantite)");
                    }
                    
                    // Récupérer ou créer le lead
                    $lead = $this->getOrCreateLead($leadTitle, $clientName);
                    
                    // Récupérer ou créer le produit
                    $product = $this->getOrCreateProduct($produit);
                    
                    // Créer une nouvelle offre pour chaque ligne
                    $offer = Offer::create([
                        'status' => OfferStatus::inProgress()->getStatus(),
                        'client_id' => $lead->client_id,
                        'external_id' => Uuid::uuid4()->toString(),
                        'source_id' => $lead->id,
                        'source_type' => Lead::class
                    ]);
                    
                    // Ajouter la ligne à l'offre
                    $invoiceLine = InvoiceLine::make([
                        'title' => $produit,
                        'type' => 'pieces',
                        'quantity' => $quantite,
                        'comment' => '',
                        'price' => $prix,
                        'product_id' => $product->id
                    ]);
                    
                    $offer->invoiceLines()->save($invoiceLine);
                    
                    // Si le type est 'invoice', convertir immédiatement l'offre en facture
                    if ($type === 'invoice') {
                        // Marquer l'offre comme gagnée et créer une facture
                        $offer->setAsWon();
                        
                        $invoice = Invoice::create($offer->toArray());
                        $invoice->offer_id = $offer->id;
                        $invoice->invoice_number = app(InvoiceNumberService::class)->setNextInvoiceNumber();
                        $invoice->status = InvoiceStatus::draft()->getStatus();
                        $invoice->save();
                        
                        // Copier les lignes de l'offre vers la facture
                        foreach ($offer->invoiceLines as $offerLine) {
                            $invoiceLine = new InvoiceLine();
                            $invoiceLine->fill($offerLine->toArray());
                            $invoiceLine->offer_id = null;
                            $invoiceLine->wasRecentlyCreated = true; // Pour éviter d'appliquer la remise à nouveau
                            $invoice->invoiceLines()->save($invoiceLine);
                        }
                    }
                }
            }
            
            // Valider la transaction
            DB::commit();
            
            return redirect()->back()
                ->with('flash_message', 'Data imported successfully');
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();
            
            return redirect()->back()
                ->with('flash_message_warning', 'Error during import: ' . $e->getMessage());
        }
    }

    /**
     * Affiche le formulaire d'exportation CSV
     */
    public function exportForm()
    {
        return view('database.export');
    }

    /**
     * Traite l'exportation CSV de toutes les tables
     */
    public function export(Request $request)
    {
        try {
            // Récupérer toutes les tables autorisées
            $tables = $this->getAvailableTables();
            
            // Créer un dossier temporaire pour stocker les fichiers CSV
            $tempDir = storage_path('app/temp_export_' . time());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Créer un fichier CSV pour chaque table
            foreach ($tables as $table) {
                $data = DB::table($table)->get();
                
                if (!$data->isEmpty()) {
                    $filePath = $tempDir . '/' . $table . '.csv';
                    $file = fopen($filePath, 'w');
                    
                    // Ajouter les en-têtes (noms des colonnes)
                    fputcsv($file, array_keys((array)$data[0]));
                    
                    // Ajouter les données
                    foreach ($data as $row) {
                        fputcsv($file, (array)$row);
                    }
                    
                    fclose($file);
                }
            }
            
            // Créer un fichier ZIP contenant tous les fichiers CSV
            $zipFileName = 'database_export_' . date('Y-m-d_His') . '.zip';
            $zipFilePath = storage_path('app/' . $zipFileName);
            
            $zip = new \ZipArchive();
            if ($zip->open($zipFilePath, \ZipArchive::CREATE) === TRUE) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tempDir),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = basename($filePath);
                        
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                
                $zip->close();
                
                // Supprimer le dossier temporaire
                $this->deleteDirectory($tempDir);
                
                // Télécharger le fichier ZIP
                return response()->download($zipFilePath)->deleteFileAfterSend(true);
            } else {
                throw new \Exception("Impossible de créer le fichier ZIP");
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('flash_message_warning', 'Erreur lors de l\'exportation : ' . $e->getMessage());
        }
    }

    /**
     * Supprime récursivement un répertoire et son contenu
     */
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Récupère la liste des tables disponibles pour la manipulation
     */
    private function getAvailableTables()
    {
        // Liste complète des tables de la base de données
        // Vous pouvez exclure certaines tables si nécessaire
        $excludedTables = [];
        
        $tables = [];
        $dbTables = DB::select('SHOW TABLES');
        $dbName = 'Tables_in_' . env('DB_DATABASE'); // Ajustez selon votre configuration
        
        foreach ($dbTables as $table) {
            $tableName = $table->$dbName;
            if (!in_array($tableName, $excludedTables)) {
                $tables[] = $tableName;
            }
        }
        
        return $tables;
    }

    /**
     * Récupère un lead existant ou en crée un nouveau
     * 
     * @param string $leadTitle Le titre du lead
     * @param Client $client Le client associé au lead
     * @return Lead Le lead récupéré ou créé
     */
    private function getOrCreateLead($leadTitle, $contactName)
    {                
        $contact = Contact::where('name', $contactName)->first();
        $client = Client::find($contact->client_id);

        if (!$client) {
            throw new \Exception("Client not found for contact '$contactName'");
        }

        $lead = Lead::where('title', $leadTitle)
            ->where('client_id', $client->id)
            ->first();
        
        if ($lead) {
            // Si le lead existe, le retourner
            return $lead;
        } else {
            // Si le lead n'existe pas, en créer un nouveau                        
            
            // Créer le lead avec une date d'échéance incluant l'heure, les minutes et les secondes
            $lead = Lead::create([
                'external_id' => Uuid::uuid4()->toString(),
                'title' => $leadTitle,
                'description' => clean($leadTitle),
                'status_id' => 7,
                'user_assigned_id' => 1, // Admin
                'user_created_id' => 1, // Admin
                'client_id' => $client->id,                
                'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
            ]);
            
            return $lead;
        }
    }

    private function getOrCreateProduct($productName)
    {
        $product = Product::where('name', $productName)->first();
        if (!$product) {
            $product = new Product();
            $product->name = $productName;
            $product->external_id = Uuid::uuid4()->toString();
            $product->description = '';
            $product->default_type = 'pieces';
            $product->price = 0;
            $product->number = '';
            $product->save();
        }
        return $product;
    }

    
} 