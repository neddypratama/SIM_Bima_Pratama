<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barangs';

    protected $fillable = [
        'name',
        'jenis_id',
        'satuan_id',
        'stok',
        'hpp'
    ];

    public function jenis()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
