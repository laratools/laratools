<?php

namespace Laratools\Eloquent;

trait Searchable
{
    public function scopeSearch($query, $term = null)
    {
        if (is_null($term)) {
            return;
        }

        $query->where(function ($subQuery) use ($term) {
            $this->getSearchableColumns()->each(function ($column) use ($subQuery, $term) {
                $subQuery->orWhere($column, 'LIKE', "%{$term}%");
            });
        });

        foreach ($this->getSearchableRelations() as $relation => $fields) {
            $query->orWhereHas($relation, function ($subQuery) use ($fields, $term) {
                $subQuery->where(function ($subSubQuery) use ($fields, $term) {
                    foreach ($fields as $field) {
                        $subSubQuery->orWhere($field, 'LIKE', "%{$term}%");
                    }
                });
            });
        }
    }

    protected function getSearchableColumns()
    {
        return $this->getAllSearchableFields()->reject(function ($field) {
            return strpos($field, '.');
        });
    }

    protected function getSearchableRelations()
    {
        $return = [];

        $this->getAllSearchableFields()->reject(function ($field) {
            return ! strpos($field, '.');
        })->each(function ($relation) use (&$return) {
            $parts = explode('.', $relation);

            $field = array_pop($parts);
            $relation = implode('.', $parts);

            $return[$relation][] = $field;
        });

        return collect($return);
    }

    protected function getAllSearchableFields()
    {
        return collect($this->searchable);
    }
}
