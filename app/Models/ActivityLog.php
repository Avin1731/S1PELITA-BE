<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Relasi ke User (Actor)
     * Untuk mengambil data: Nama, Role, Jenis DLH, Provinsi, Kab/Kota di UI
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi Polymorphic ke Subject (Target Aksi)
     * Misal: User yang diedit, Dokumen yang diupload, dll.
     */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Helper Static untuk mencatat log dengan mudah & bersih dari Controller.
     * * Cara Pakai:
     * ActivityLog::record('approve_user', 'Menyetujui User A', $targetUser);
     * * @param string $action (Slug aksi, misal: 'create_pusdatin')
     * @param string $description (Pesan yang tampil di UI)
     * @param Model|null $subject (Objek target, optional)
     * @param array|null $properties (Data tambahan/backup, optional)
     */
    public static function record($action, $description, $subject = null, $properties = null)
    {
        // Cek user login, jika via seeder/job mungkin null
        $userId = Auth::check() ? Auth::id() : null;

        self::create([
            'user_id'      => $userId,
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject ? $subject->id : null,
            'properties'   => $properties,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::header('User-Agent'),
        ]);
    }
}