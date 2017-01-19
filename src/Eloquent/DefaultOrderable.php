<?php

namespace Laratools\Eloquent;

trait DefaultOrderable
{
    public static function bootDefaultOrderable()
    {
        static::addGlobalScope(new DefaultOrderableScope());
    }
}
