<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barangs';

    protected $fillable = [
        'name',
        'jenis_id',
    ];

    public function jenis()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_id');
    }

    public function details()
    {
        return $this->hasMany(DetailTransaksi::class);
    }

    public function stokBatches()
    {
        return $this->hasMany(StokBatch::class);
    }
}
