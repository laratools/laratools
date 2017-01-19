<?php

namespace Laratools\Eloquent;

use Illuminate\Contracts\Encryption\DecryptException;
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
        if ($this->is_encrypted) {
            try {
                $value = app('encrypter')->decrypt($value);
            } catch (DecryptException $e) {
            }
        }

        return $value;
    }
}
