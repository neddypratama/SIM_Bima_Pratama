<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategoris';

    protected $fillable = [
        'name',
        'deskripsi',
    ];
    public function detail()
    {
        return $this->hasMany(DetailTransaksi::class);
    }
}
