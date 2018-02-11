<?php

namespace Laratools\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid as UuidGenerator;

trait Uuid
{
    public static function bootUuid()
    {
        static::creating(function (Model $model) {
            if (is_null($model->getAttribute($model->getUuidColumn()))) {
                $model->setAttribute($model->getUuidColumn(), (string)$model->generateUuid());
            }
        });
    }

    public function scopeUuid(Builder $query, $uuid)
    {
        if (is_array($uuid)) {
            return $query->whereIn($this->getUuidColumn(), $uuid);
        }

        return $query->where($this->getUuidColumn(), $uuid);
    }

    public function getQualifiedUuidColumn()
    {
        return $this->getTable() . '.' . $this->getUuidColumn();
    }

    public function getUuidColumn()
    {
        return defined('static::UUID_COLUMN') ? static::UUID_COLUMN : 'uuid';
    }

    public function generateUuid()
    {
        return UuidGenerator::uuid4();
    }
}
