<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, MilikRuangKerja;

    protected $fillable = [
        'workspace_id',
        'nama',
        'kod',
        'pegawai_perhubungan',
        'telefon',
        'emel',
        'alamat',
        'aktif',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
