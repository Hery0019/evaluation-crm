<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remise extends Model
{
    protected $table = 'remise';
    protected $primaryKey = 'id';

    protected $filiable = [
        'pourcentage',
    ];
    

    public $timestamp = true; // si vous avez ajouter created_at ou updated_at
}
