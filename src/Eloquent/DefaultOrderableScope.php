<?php

namespace Laratools\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DefaultOrderableScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $table = $model->getTable();
        $columns = $model->getDefaultOrderableColumns() ?: [];

        foreach ($columns as $column => $order) {
            $builder->orderBy("{$table}.{$column}", $order);
        }
    }
}
