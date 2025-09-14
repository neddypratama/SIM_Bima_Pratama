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
        'jenis_id'
    ];
    public function detail()
    {
        return $this->hasMany(DetailTransaksi::class);
    }

    public function jenis()
    {
        return $this->belongsTo(JenisBarang::class);
    }
}
