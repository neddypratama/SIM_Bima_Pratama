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
}
