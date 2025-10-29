<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'dinas_id',
        'email',
        'password',
        'role',
        'is_active',
    ];
    use \Laravel\Sanctum\HasApiTokens;
    public function dinas(){
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }
}
