<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends Model
{
    protected $table = 'transaksis';
    protected $fillable = ['invoice', 'tanggal', 'name', 'type', 'total', 'user_id', 'client_id', 'kategori_id', 'linked_id'];

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

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'linked_id');
    }

    /**
     * Relasi kebalikan, transaksi ini punya transaksi lain yang terhubung.
     */
    public function linkKas(): HasOne
    {
        return $this->hasOne(Transaksi::class, 'linked_id');
    }
}
