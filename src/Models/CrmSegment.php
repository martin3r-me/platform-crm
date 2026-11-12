<?php

namespace Platform\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

/**
 * CRM-Lookup "segments" (Segmentierung / Verticals).
 *
 * Datengetrieben und mandantenspezifisch pflegbar (UI + MCP) – kein Hardcode.
 * Wird leer ausgeliefert; Werte sind Mandanten-Fachdaten.
 */
class CrmSegment extends Model
{
    protected $table = 'crm_segments';

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());

                $model->uuid = $uuid;
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
