<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $fillable = [
        'barang_id',
        'tanggal',
        'type',
        'jumlah',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
