<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKategori extends Model
{
    protected $table = 'detail_kategoris';

    protected $fillable = [
        'name',
        'deskripsi',
        'type',
    ];

    public function kategoris()
    {
        return $this->hasMany(Kategori::class);
    }
}
