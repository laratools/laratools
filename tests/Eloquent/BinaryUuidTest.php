<?php

namespace Laratools\Eloquent;

use PHPUnit_Framework_TestCase;
use Ramsey\Uuid\Uuid as UuidGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class BinaryUuidTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
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

        UuidOrder::flushEventListeners();
        UuidOrder::boot();

        RenamedUuidColumnPayment::flushEventListeners();
        RenamedUuidColumnPayment::boot();
    }

    /**
     * Setup database schema
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('orders', function(Blueprint $table)
        {
            $table->increments('id');
            $table->binary('uuid', 16);
        });

        $this->schema()->create('payments', function(Blueprint $table)
        {
            $table->increments('id');
            $table->binary('guid', 16);
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('orders');
        $this->schema()->drop('payments');
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    public function test_it_should_serialize_when_no_uuid_has_been_set()
    {
        $order = new UuidOrder();

        $order->forceFill(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar', 'uuid' => null], $order->toArray());
        $this->assertSame('{"foo":"bar","uuid":null}', $order->toJson());
    }

    public function test_it_should_automatically_generate_a_uuid_when_creating_a_model()
    {
        $invoice = UuidOrder::create();

        $this->assertNotNull($invoice->uuid);
    }

    public function test_it_should_not_generate_a_uuid_if_one_has_already_been_set()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = UuidOrder::create(['uuid' => $uuid->getBytes()]);

        $this->assertSame($uuid->getBytes(), $invoice->uuid);
        $this->assertSame($uuid->toString(), $invoice->uuid_text);
    }

    public function test_it_should_set_the_uuid_attribute_when_setting_the_uuid_text_mutator()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = UuidOrder::create(['uuid_text' => $uuid->toString()]);

        $this->assertSame($uuid->getBytes(), $invoice->uuid);
        $this->assertSame($uuid->toString(), $invoice->uuid_text);
    }

    public function test_it_should_fetch_a_model_using_the_uuid_scope()
    {
        $a = UuidOrder::create();
        $b = UuidOrder::create();
        $c = UuidOrder::create();

        $order = UuidOrder::uuid($b->uuid)->first();

        $this->assertInstanceOf(UuidOrder::class, $order);
        $this->assertSame($b->uuid, $order->uuid);
        $this->assertSame($b->uuid_text, $order->uuid_text);
    }

    public function test_it_should_fetch_multiple_models_using_the_uuid_scope()
    {
        $a = UuidOrder::create();
        $b = UuidOrder::create();
        $c = UuidOrder::create();
        $d = UuidOrder::create();

        $orders = UuidOrder::uuid([$b->uuid, $d->uuid])->get();

        $this->assertSame(2, $orders->count());

        $this->assertSame($b->uuid, $orders->get(0)->uuid);
        $this->assertSame($b->uuid_text, $orders->get(0)->uuid_text);

        $this->assertSame($d->uuid, $orders->get(1)->uuid);
        $this->assertSame($d->uuid_text, $orders->get(1)->uuid_text);
    }

    public function test_it_should_serialize_when_no_uuid_has_been_set_with_a_custom_column()
    {
        $payment = new RenamedUuidColumnPayment();

        $payment->forceFill(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar', 'guid' => null], $payment->toArray());
        $this->assertSame('{"foo":"bar","guid":null}', $payment->toJson());
    }

    public function test_it_should_detect_a_custom_column()
    {
        $invoice = RenamedUuidColumnPayment::create();

        $this->assertSame('guid', $invoice->getUuidColumn());
    }

    public function test_it_should_automatically_generate_a_uuid_when_creating_a_model_with_a_custom_column()
    {
        $invoice = RenamedUuidColumnPayment::create();

        $this->assertNotNull($invoice->guid);
    }

    public function test_it_should_not_automatically_generate_a_uuid_if_one_has_already_been_set_with_a_custom_column()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = RenamedUuidColumnPayment::create(['guid' => $uuid->getBytes()]);

        $this->assertSame($uuid->getBytes(), $invoice->guid);
        $this->assertSame($uuid->toString(), $invoice->uuid_text);
        $this->assertSame($uuid->toString(), $invoice->guid_text);
    }

    public function test_it_should_set_the_uuid_attribute_when_setting_the_uuid_text_mutator_and_using_a_custom_column()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = RenamedUuidColumnPayment::create(['guid_text' => $uuid->toString()]);

        $this->assertSame($uuid->getBytes(), $invoice->guid);
        $this->assertSame($uuid->toString(), $invoice->guid_text);
    }

    public function test_it_should_fetch_a_model_using_the_uuid_scope_when_using_a_custom_column()
    {
        $a = RenamedUuidColumnPayment::create();
        $b = RenamedUuidColumnPayment::create();
        $c = RenamedUuidColumnPayment::create();

        $payment = RenamedUuidColumnPayment::guid($b->guid)->first();

        $this->assertInstanceOf(RenamedUuidColumnPayment::class, $payment);
        $this->assertSame($b->guid, $payment->guid);
        $this->assertSame($b->guid_text, $payment->guid_text);
    }

    public function test_it_should_fetch_multiple_models_using_the_uuid_scope_when_using_a_custom_column()
    {
        $a = RenamedUuidColumnPayment::create();
        $b = RenamedUuidColumnPayment::create();
        $c = RenamedUuidColumnPayment::create();
        $d = RenamedUuidColumnPayment::create();

        $payments = RenamedUuidColumnPayment::guid([$b->guid, $d->guid])->get();

        $this->assertSame(2, $payments->count());

        $this->assertSame($b->guid, $payments->get(0)->guid);
        $this->assertSame($b->guid_text, $payments->get(0)->guid_text);

        $this->assertSame($d->guid, $payments->get(1)->guid);
        $this->assertSame($d->guid_text, $payments->get(1)->guid_text);
    }
}

/**
 * Models
 */
class UuidOrder extends Eloquent
{
    use BinaryUuid;

    public $timestamps = false;

    protected $table = 'orders';

    protected $guarded = [];
}

class RenamedUuidColumnPayment extends Eloquent
{
    use BinaryUuid;

    const UUID_COLUMN = 'guid';

    public $timestamps = false;

    protected $table = 'payments';

    protected $guarded = [];

    public function scopeGuid($query, $guid)
    {
        return $this->scopeUuid($query, $guid);
    }

    public function getGuidTextAttribute(): string
    {
        return $this->uuid_text;
    }

    public function setGuidTextAttribute(string $uuid)
    {
        $this->uuid_text = $uuid;
    }
}
