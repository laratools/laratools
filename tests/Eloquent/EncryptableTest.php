<?php

namespace Laratools\Eloquent;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EncryptableTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $db = new DB();

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
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
    public function tearDown()
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

        $encrypter->shouldReceive('decrypt')
                  ->once()
                  ->with('eyJpdiI6Im56UHp1OENQOVdvS2VHSnhGbDJGMWc9PSIsInZhbHVlIjoiZXFpendPWmxlNHJGeDBqSzBtalNCaFY3WG16RTYxeE12N2NKUUJMRzlsOD0iLCJtYWMiOiIyZmQxZDhhNzI1NWVlODgwNWQwMTQzN2I3MDJjNjgzOGRhZThmYjYwMTI4ZjM1NGUxYWQ4ZDE4NzZjNDE1MGI1In0=')
                  ->andReturn('super secret value');

        EncryptableCustomer::setEncrypter($encrypter);

        $customer = EncryptableCustomer::create([
            'secret' => 'super secret value',
        ]);

        $this->assertSame('super secret value', $customer->secret);
        $this->assertNotSame('super secret value', Arr::get($customer->getAttributes(), 'secret', null));
        $this->assertSame(
            'eyJpdiI6Im56UHp1OENQOVdvS2VHSnhGbDJGMWc9PSIsInZhbHVlIjoiZXFpendPWmxlNHJGeDBqSzBtalNCaFY3WG16RTYxeE12N2NKUUJMRzlsOD0iLCJtYWMiOiIyZmQxZDhhNzI1NWVlODgwNWQwMTQzN2I3MDJjNjgzOGRhZThmYjYwMTI4ZjM1NGUxYWQ4ZDE4NzZjNDE1MGI1In0=',
            Arr::get($customer->getAttributes(), 'secret', null)
        );
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

        $this->connection()->insert("INSERT INTO `customers` ('secret') VALUES (?)", ['my unencrypted string']);

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

        $this->connection()->insert("INSERT INTO `orders` ('secret') VALUES (?)", ['my unencrypted string']);

        $order = UnsafeEncryptableOrder::findOrFail(1);

        $order->secret;
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

    protected $encryptable = ['secret'];
}

class UnsafeEncryptableOrder extends Eloquent
{
    use Encryptable;

    protected $table = 'orders';

    protected $guarded = [];

    protected $encryptable = ['secret'];

    protected $safeDecrypt = false;
}