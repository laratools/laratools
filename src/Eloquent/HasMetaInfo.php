<?php

namespace Laratools\Eloquent;

trait HasMetaInfo
{
    public function meta()
    {
        return $this->morphMany(Meta::class, 'owner');
    }

    public function hasMeta($key)
    {
        return $this->meta()->where('key', $key)->count() > 0;
    }

    public function getMeta($key, $default = null)
    {
        $meta = $this->meta()->where('key', $key)->first();

        return data_get($meta, 'value', $default);
    }

    public function setMeta($key, $value)
    {
        if (is_null($value)) {
            return $this->deleteMeta($key);
        }

        $is_encrypted = false;

        if ($this->shouldBeEncrypted($key)) {
            $value = app('encrypter')->encrypt($value);

            $is_encrypted = true;
        }

        return $this->meta()->updateOrCreate(compact('key'), compact('value', 'is_encrypted'));
    }

    public function deleteMeta($key)
    {
        return $this->meta()->where('key', $key)->delete();
    }

    public function shouldBeEncrypted($key)
    {
        $instance = new self;

        $should = method_exists($instance, 'getMetaEncryptedKeys') ? $instance->getMetaEncryptedKeys() : [];

        return in_array($key, $should);
    }
}
