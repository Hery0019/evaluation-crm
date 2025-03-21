<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;

class ImportUserController extends Controller
{
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt',
        ]);

        Excel::import(new UsersImport, request()->file('file'), null, \Maatwebsite\Excel\Excel::CSV);

        return back()->with('success', 'Importation réussie !');
    }
}
