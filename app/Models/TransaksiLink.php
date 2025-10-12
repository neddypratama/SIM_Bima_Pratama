<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiLink extends Model
{
    protected $table = 'transaksi_links';
    protected $fillable = ['transaksi_id', 'linked_id'];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id', 'id');
    }

    public function linkedTransaksi()
    {
        return $this->belongsTo(Transaksi::class, 'linked_id', 'id');
    }

}
