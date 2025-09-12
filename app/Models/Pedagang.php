<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedagang extends Model
{
    protected $table = 'pedagangs';

    protected $fillable = [
        'name',
        'alamat',
    ];
}
