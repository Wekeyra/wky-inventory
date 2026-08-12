<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Mengehadkan model kepada ruang kerja pengguna yang sedang log masuk, dan
 * menandakan baris baharu dengan ruang kerja itu secara automatik.
 *
 * Skop dipasang pada peringkat model, bukan pada setiap pertanyaan, supaya
 * tiada satu pun laluan boleh terlepas — termasuk pengikatan model pada laluan,
 * yang akan memulangkan 404 apabila rekod itu milik ruang kerja lain.
 *
 * Apabila tiada sesiapa log masuk (seeder, migrasi, arahan konsol) skop tidak
 * dipasang, kerana konteks itu memang perlu melihat semua ruang kerja.
 */
trait MilikRuangKerja
{
    public static function bootMilikRuangKerja(): void
    {
        static::addGlobalScope('ruang_kerja', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            // Dinamakan penuh dengan jadual kerana pertanyaan berpaut boleh
            // mempunyai lebih daripada satu lajur workspace_id.
            $builder->where(
                $builder->getModel()->getTable().'.workspace_id',
                Auth::user()->workspace_id,
            );
        });

        static::creating(function (Model $model) {
            if (blank($model->workspace_id)) {
                $model->workspace_id = Auth::user()?->workspace_id;
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
