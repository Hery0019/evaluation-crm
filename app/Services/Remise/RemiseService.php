<?php

namespace App\Services\Remise;

use Illuminate\Support\Facades\DB;

class RemiseService
{
    public static function getPourcent(): float {
        $result =DB::select("SELECT pourcentage FROM remise WHERE id = 1");
        return $result[0]->pourcentage ?? 0;
    }
}
