<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peternak extends Model
{
    protected $table = 'peternaks';

    protected $fillable = [
        'name',
        'alamat',
    ];
}
