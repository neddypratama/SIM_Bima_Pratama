<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Truk extends Model
{
    protected $table = 'truks';
    protected $fillable = ['invoice', 'tanggal', 'name', 'type', 'total', 'user_id', 'client_id',];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // di Transaksi.php
    public function linked()
    {
        return $this->hasMany(TransaksiLink::class, 'transaksi_id');
    }

    public function linkedTransaksis()
    {
        return $this->belongsToMany(Transaksi::class, 'transaksi_links', 'transaksi_id', 'linked_id');
    }


}
