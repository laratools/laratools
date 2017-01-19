<?php

namespace Laratools\Eloquent;

trait Archivable
{
    public static function bootArchivable()
    {
        static::addGlobalScope(new ArchivableScope());
    }

    public function sendToArchive()
    {
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getArchivedAtColumn()} = $time = $this->freshtimestamp();

        $query->update([$this->getArchivedAtColumn() => $this->fromDateTime($time)]);
    }

    public function restoreFromArchive()
    {
        $this->{$this->getArchivedAtColumn()} = null;

        return $this->save();
    }

    public function isArchived()
    {
        return ! is_null($this->getAttribute($this->getArchivedAtColumn()));
    }

    public static function withArchived()
    {
        return (new static)->newQueryWithoutScope(new ArchivableScope());
    }

    public static function onlyArchived()
    {
        $instance = new static;

        $column = $instance->getQualifiedArchivedAtColumn();

        return $instance->newQueryWithoutScope(new ArchivableScope())->whereNotNull($column);
    }

    public function getArchivedAtColumn()
    {
        return defined('self::ARCHIVED_AT_COLUMN') ? self::ARCHIVED_AT_COLUMN : 'archived_at';
    }

    public function getQualifiedArchivedAtColumn()
    {
        return $this->getTable().'.'.$this->getArchivedAtColumn();
    }
}
