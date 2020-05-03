<?php

namespace Laratools\Eloquent;

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Mockery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class EncryptableTest extends TestCase
{
    public function setUp(): void
    {
        $db = new DB();

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        Eloquent::setEventDispatcher(new \Illuminate\Events\Dispatcher());

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();

        EncryptableCustomer::flushEventListeners();
        EncryptableCustomer::boot();

        UnsafeEncryptableOrder::flushEventListeners();
        UnsafeEncryptableOrder::boot();
    }

    /**
     * Setup the database schema
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('customers', function(Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->string('secret')->nullable();
            $table->string('not_secret')->nullable();
        });

        $this->schema()->create('orders', function(Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->string('secret')->nullable();
            $table->string('not_secret')->nullable();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->schema()->drop('customers');
        $this->schema()->drop('orders');
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    public function test_it_does_encrypt_attributes()
    {
        $encrypter = Mockery::mock(Encrypter::class);

        $encrypter->shouldReceive('encrypt')
                  ->once()
                  ->with('super secret value')
                  ->andReturn('eyJpdiI6Im56UHp1OENQOVdvS2VHSnhGbDJGMWc9PSIsInZhbHVlIjoiZXFpendPWmxlNHJGeDBqSzBtalNCaFY3WG16RTYxeE12N2NKUUJMRzlsOD0iLCJtYWMiOiIyZmQxZDhhNzI1NWVlODgwNWQwMTQzN2I3MDJjNjgzOGRhZThmYjYwMTI4ZjM1NGUxYWQ4ZDE4NzZjNDE1MGI1In0=');

        EncryptableCustomer::setEncrypter($encrypter);

        EncryptableCustomer::create([
            'secret' => 'super secret value',
        ]);
    }

    public function test_it_does_not_encrypt_attributes_it_shouldnt()
    {
        $encrypter = Mockery::mock(Encrypter::class);

        $encrypter->shouldNotReceive('encrypt');
        $encrypter->shouldNotReceive('decrypt');

        EncryptableCustomer::setEncrypter($encrypter);

        $customer = EncryptableCustomer::create([
            'not_secret' => 'my unsecure thing',
        ]);

        $this->assertSame('my unsecure thing', $customer->not_secret);
    }

    public function test_it_can_safely_decrypt_an_unencrypted_string()
    {
        $encrypter = Mockery::mock(Encrypter::class);

        $encrypter->shouldReceive('decrypt')
                  ->once()
                  ->with('my unencrypted string')
                  ->andReturn('my unencrypted string');

        EncryptableCustomer::setEncrypter($encrypter);

        $this->connection()->insert("INSERT INTO `customers` ('secret', 'created_at', 'updated_at') VALUES (?, ?, ?)", ['my unencrypted string', Carbon::now(), Carbon::now()]);

        $customer = EncryptableCustomer::findOrFail(1);

        $this->assertSame('my unencrypted string', $customer->secret);
    }

    public function test_it_throws_an_exception_when_attempting_to_decrypt_an_unencrypted_string()
    {
        $this->expectException(DecryptException::class);

        $encrypter = Mockery::mock(Encrypter::class);

        $encrypter->shouldReceive('decrypt')
            ->once()
            ->with('my unencrypted string')
            ->andThrow(DecryptException::class);

        UnsafeEncryptableOrder::setEncrypter($encrypter);

        $this->connection()->insert("INSERT INTO `orders` ('secret', 'created_at', 'updated_at') VALUES (?, ?, ?)", ['my unencrypted string', 'NOW()', 'NOW()']);

        $order = UnsafeEncryptableOrder::findOrFail(1);

        $order->secret;
    }

    public function test_it_decodes_attributes_when_the_model_is_cast_to_an_array()
    {
        $encrypter = Mockery::mock(Encrypter::class);

        $encrypter->shouldReceive('encrypt')
            ->once()
            ->with('super secret')
            ->andReturn('eyJpdiI6Im56UHp1OENQOVdvS2VHSnhGbDJGMWc9PSIsInZhbHVlIjoiZXFpendPWmxlNHJGeDBqSzBtalNCaFY3WG16RTYxeE12N2NKUUJMRzlsOD0iLCJtYWMiOiIyZmQxZDhhNzI1NWVlODgwNWQwMTQzN2I3MDJjNjgzOGRhZThmYjYwMTI4ZjM1NGUxYWQ4ZDE4NzZjNDE1MGI1In0=');

        $encrypter->shouldReceive('decrypt')
            ->once()
            ->with('eyJpdiI6Im56UHp1OENQOVdvS2VHSnhGbDJGMWc9PSIsInZhbHVlIjoiZXFpendPWmxlNHJGeDBqSzBtalNCaFY3WG16RTYxeE12N2NKUUJMRzlsOD0iLCJtYWMiOiIyZmQxZDhhNzI1NWVlODgwNWQwMTQzN2I3MDJjNjgzOGRhZThmYjYwMTI4ZjM1NGUxYWQ4ZDE4NzZjNDE1MGI1In0=')
            ->andReturn('super secret');

        EncryptableCustomer::setEncrypter($encrypter);

        $customer = EncryptableCustomer::create([
            'secret' => 'super secret',
        ]);

        $array = $customer->toArray();

        $this->assertSame('super secret', Arr::get($array, 'secret', null));
    }
}

/**
 * Models
 */
class EncryptableCustomer extends Eloquent
{
    use Encryptable;

    protected $table = 'customers';

    protected $guarded = [];

    protected $casts = [
        'secret' => 'encrypt',
    ];
}

class UnsafeEncryptableOrder extends Eloquent
{
    use Encryptable;

    protected $table = 'orders';

    protected $guarded = [];

    protected $casts = [
        'secret' => 'encrypt',
    ];

    protected $safeDecrypt = false;
}
