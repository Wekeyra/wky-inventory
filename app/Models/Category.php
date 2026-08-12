<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, MilikRuangKerja;

    protected $fillable = ['workspace_id', 'nama', 'kod', 'keterangan'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
