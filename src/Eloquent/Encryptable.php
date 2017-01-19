<?php

namespace Laratools\Eloquent;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;

trait Encryptable
{
    /**
     * The encrypter instance
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    static $encrypter;

    /**
     * Register the listener to the 'saving' event to encrypt the model's attributes
     *
     * @return void
     */
    public static function bootEncryptable()
    {
        static::saving(function (Model $model) {
            foreach ($model->getAttributes() as $attribute => $value) {
                if ($model->isEncryptableAttribute($attribute)) {
                    $value = $model->encrypt($value);

                    $model->setAttribute($attribute, $value);
                }
            }
        });
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
     * Decrypts an encryptable value via. Eloquent's cast system
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        switch ($this->getCastType($key)) {
            case 'encrypt':
                return $this->decrypt($value);
            default:
                return parent::castAttribute($key, $value);
        }
    }
    /**
     * Checks if the given attribute is encryptable
     *
     * @param $attribute
     * @return bool
     */
    protected function isEncryptableAttribute($attribute)
    {
        return $this->hasCast($attribute) && $this->getCastType($attribute) === 'encrypt';
    }

    /**
     * Decrypt the given value.
     *
     * @param string $value
     * @return string
     */
    public function decrypt($value)
    {
        if ($this->shouldSafelyDecrypt())
        {
            return $this->safelyDecrypt($value);
        }

        return $this->getEncrypter()->decrypt($value);
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
            return $this->getEncrypter()->decrypt($value);
        } catch(DecryptException $e) {}

        return $value;
    }

    /**
     * Encrypt the given value.
     *
     * @param string $value
     * @return string
     */
    public function encrypt($value)
    {
        return $this->getEncrypter()->encrypt($value);
    }

    /**
     * Get the encrypter instance.
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    protected function getEncrypter()
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
