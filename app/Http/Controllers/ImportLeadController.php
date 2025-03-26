<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LeadsImport;

class ImportLeadController extends Controller
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
    
        Excel::import(new LeadsImport, $file);
    
        return back()->with('success', 'Importation réussie !');
    }
    
}
