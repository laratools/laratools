<?php

namespace Laratools\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ArchivableScope implements Scope
{
    protected $extensions = ['RestoreFromArchive', 'WithArchived', 'OnlyArchived'];

    public function apply(Builder $builder, Model $model)
    {
        $builder->whereNull($model->getQualifiedArchivedAtColumn());
    }

    public function extend(Builder $builder)
    {
        foreach($this->extensions as $extension)
        {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function getArchivedAtColumn(Builder $builder)
    {
        if (count($builder->getQuery()->joins) > 0)
        {
            return $builder->getModel()->getQualifiedArchivedAtColumn();
        }

        return $builder->getModel()->getArchivedAtColumn();
    }

    protected function addRestoreFromArchive(Builder $builder)
    {
        $builder->macro('restoreFromArchive', function(Builder $builder)
        {
            return $builder->getModel()->update([
                $this->getArchivedAtColumn($builder) => null,
            ]);
        });
    }

    protected function addWithArchived(Builder $builder)
    {
        $builder->macro('withArchived', function(Builder $builder)
        {
            $this->remove($builder, $builder->getModel());

            return $builder;
        });
    }

    protected function addOnlyArchived(Builder $builder)
    {
        $builder->macro('onlyArchived', function(Builder $builder)
        {
            $model = $builder->getModel();

            $this->remove($builder, $model);

            $builder->getQuery()->whereNotNull($model->getQualifiedArchivedAtColumn());

            return $builder;
        });
    }
}
