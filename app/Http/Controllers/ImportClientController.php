<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ClientsImport;

class ImportClientController extends Controller
{
    public function importCsv(Request $request)
    {
        if (!$request->hasFile('csv_file')) {
            return back()->with('error', 'Aucun fichier n\'a été sélectionné.');
        }
    
        $file = $request->file('csv_file');
    
        if (!$file->isValid()) {
            return back()->with('error', 'Le fichier n\'est pas valide.');
        }
    
        Excel::import(new ClientsImport, $file);
    
        return back()->with('success', 'Importation réussie !');
    }
    
}
