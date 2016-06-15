<?php

namespace Laratools\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $table = 'meta_information';

    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public function owner()
    {
        return $this->morphTo();
    }

    public function getValueAttribute($value)
    {
        if ($this->is_encrypted)
        {
            return app('encrypter')->decrypt($value);
        }

        return $value;
    }
}