<?php

namespace App\Models\Pusdatin;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pusdatin\Parsed\Validasi2Parsed;

class Validasi2 extends Model
{
    protected $table = 'validasi_2';
    
    protected $fillable = [
        'validasi_1_id',
        'year',
        'status',
        'is_finalized',
        'finalized_at',
        'catatan',
        'error_messages',
        'finalized_by',
    ];
    
    protected $casts = [
        'is_finalized' => 'boolean',
        'finalized_at' => 'datetime',
    ];

    public function validasi1()
    {
        return $this->belongsTo(Validasi1::class, 'validasi_1_id');
    }

    public function validasi2Parsed()
    {
        return $this->hasMany(Validasi2Parsed::class, 'validasi_2_id');
    }
    
    public function finalizedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'finalized_by');
    }
}
