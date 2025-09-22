<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = ['name', 'alamat', 'type'];

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }   

    public function getBonAttribute(): float
    {
        $transaksi = $this->transaksi()
            ->whereHas('kategori', fn($q) => $q->where('name', 'like', 'Piutang%'))
            ->get();

        $totalDebit  = $transaksi->where('type', 'Debit')->sum('total');
        $totalKredit = $transaksi->where('type', 'Kredit')->sum('total');

        return $totalDebit - $totalKredit;
    }

    public function getTitipanAttribute(): float
    {
        $transaksi = $this->transaksi()
            ->whereHas('kategori', fn($q) => $q->where('name', 'like', 'Hutang%'))
            ->get();

        $totalDebit  = $transaksi->where('type', 'Debit')->sum('total');
        $totalKredit = $transaksi->where('type', 'Kredit')->sum('total');

        return $totalKredit - $totalDebit;
    }

}
