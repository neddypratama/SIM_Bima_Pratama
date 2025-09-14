<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    protected $table = 'detail_transaksis';
    protected $fillable = ['transaksi_id', 'type', 'value', 'barang_id', 'kuantitas', 'client_id', 'bagian', 'kategori_id'];
    
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}
