<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends Model
{
    protected $table = 'transaksis';
    protected $fillable = ['invoice', 'tanggal', 'name', 'type', 'total', 'user_id', 'client_id',];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function details()
    {
        return $this->hasMany(DetailTransaksi::class);
    }

}
