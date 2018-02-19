<?php

namespace Laratools\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Ramsey\Uuid\Uuid as UuidGenerator;

trait BinaryUuid
{
    use Uuid {
        Uuid::generateUuid as generateUuidString;
        Uuid::scopeUuid as scopeUuidString;
    }

    public function scopeUuid(Builder $query, $uuid)
    {
        if (is_array($uuid)) {
            $uuid = array_map(function ($param) {
                return static::encodeUuid($param);
            }, $uuid);
        } else {
            $uuid = static::encodeUuid($uuid);
        }

        return $this->scopeUuidString($query, $uuid);
    }

    public function generateUuid()
    {
        return static::encodeUuid($this->generateUuidString());
    }

    public static function encodeUuid($uuid): string
    {
        if (! UuidGenerator::isValid($uuid)) {
            return $uuid;
        }

        if (! $uuid instanceof UuidGenerator) {
            $uuid = UuidGenerator::fromString($uuid);
        }

        return $uuid->getBytes();
    }

    public static function decodeUuid(string $binary): string
    {
        if (UuidGenerator::isValid($binary)) {
            return $binary;
        }

        return UuidGenerator::fromBytes($binary)->toString();
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), [$this->getUuidColumn() => $this->uuid_text]);
    }

    public function getUuidTextAttribute(): string
    {
        return static::decodeUuid($this->{$this->getUuidColumn()});
    }

    public function setUuidTextAttribute(string $uuid)
    {
        $this->{$this->getUuidColumn()} = static::encodeUuid($uuid);
    }
}
