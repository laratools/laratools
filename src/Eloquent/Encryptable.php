<?php

namespace Laratools\Eloquent;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

trait Encryptable
{
    /**
     * The encrypter instance
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    static $encrypter;

    /**
     * Get the array of attributes that should be encrypted/decrypted
     *
     * @return array
     */
    protected function getEncryptableAttributes()
    {
        return $this->encryptable ?: [];
    }

    /**
     * If the decryption should be safely attempted by using a try/catch
     *
     * @return bool
     */
    public function shouldSafelyDecrypt()
    {
        return property_exists($this, 'safeDecrypt') ? (bool) $this->safeDecrypt : true;
    }

    /**
     * Overwrite Eloquent's function to decrypt the value before it's used elsewhere in the model
     *
     * @param string $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if ($this->isEncryptableAttribute($key)) {
            return $this->decrypt(parent::getAttributeFromArray($key));
        }

        return parent::getAttributeFromArray($key);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // Let the parent handle things first so mutations can happen
        parent::setAttribute($key, $value);

        if ($this->isEncryptableAttribute($key))
        {
            // Get the raw value from the array as it may have been mutated
            $value = $this->attributes[$key];

            $this->attributes[$key] = $this->encrypt($value);
        }

        return $this;
    }

    /**
     * If the given attribute should be encrypted/decrypted.
     *
     * @param string $key
     * @return bool
     */
    protected function isEncryptableAttribute($key)
    {
        return in_array($key, $this->getEncryptableAttributes());
    }

    /**
     * Decrypt the given value.
     *
     * @param string $value
     * @return string
     */
    protected function decrypt($value)
    {
        if ($this->shouldSafelyDecrypt())
        {
            return $this->safelyDecrypt($value);
        }

        return $this->encrypter()->decrypt($value);
    }

    /**
     * Safely attempt to decrypt the attribute without throwing exceptions.
     *
     * @param string $value
     * @return string
     */
    protected function safelyDecrypt($value)
    {
        try {
            return $this->encrypter()->decrypt($value);
        } catch(DecryptException $e) {}

        return $value;
    }

    /**
     * Encrypt the given value.
     *
     * @param string $value
     * @return string
     */
    protected function encrypt($value)
    {
        return $this->encrypter()->encrypt($value);
    }

    /**
     * Get the encrypter instance.
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    protected function encrypter()
    {
        if (static::$encrypter instanceof Encrypter) return static::$encrypter;

        return app('encrypter');
    }

    /**
     * Set a specific encrypter instance.
     *
     * @param Encrypter $encrypter
     * @return void
     */
    public static function setEncrypter(Encrypter $encrypter)
    {
        static::$encrypter = $encrypter;
    }
}
