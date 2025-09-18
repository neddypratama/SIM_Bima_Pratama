<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisBarang extends Model
{
    protected $table = 'jenis_barangs';

    protected $fillable = [
        'name',
        'deskripsi',
        'kategori_id'
    ];

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'jenis_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}
