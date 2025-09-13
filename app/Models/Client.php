<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';
    protected $fillable = ['name', 'alamat', 'type'];
    public function detailTransaksis()
    {
        return $this->hasMany(DetailTransaksi::class);
    }   
}
