<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ruang kerja sebuah syarikat. Semua inventori, pembekal, pergerakan stok dan
 * pengguna dimiliki oleh satu ruang kerja, dan tidak pernah bertemu data ruang
 * kerja lain.
 */
class Workspace extends Model
{
    use HasFactory;

    protected $fillable = ['nama'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
