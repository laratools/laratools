<?php

namespace Laratools\Eloquent;

trait DefaultOrderable
{
    public static function bootOrderable()
    {
        static::addGlobalScope(new DefaultOrderableScope());
    }
}