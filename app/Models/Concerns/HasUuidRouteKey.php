<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUuidRouteKey
{
    protected static function bootHasUuidRouteKey(): void
    {
        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $instance = $this->newQuery();

        if ($field) {
            return $instance->where($field, $value)->first();
        }

        $model = $instance->where($this->getRouteKeyName(), $value)->first();

        if ($model) {
            return $model;
        }

        if (is_numeric($value)) {
            return $instance->where($this->getKeyName(), $value)->first();
        }

        return null;
    }
}
