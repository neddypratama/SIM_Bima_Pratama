<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategoris';

    protected $fillable = [
        'name',
        'deskripsi',
        'type',
    ];
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function jenis() {
        return $this->hasMany(JenisBarang::class);
    }
}
