<?php

namespace Laratools\Eloquent;

use PHPUnit_Framework_TestCase;
use Ramsey\Uuid\Uuid as UuidGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class UuidTest extends PHPUnit_Framework_TestCase
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

        UuidInvoice::flushEventListeners();
        UuidInvoice::boot();

        RenamedUuidColumnProject::flushEventListeners();
        RenamedUuidColumnProject::boot();
    }

    /**
     * Setup database schema
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('invoices', function(Blueprint $table)
        {
            $table->increments('id');
            $table->binary('uuid', 16);
        });

        $this->schema()->create('projects', function(Blueprint $table)
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
        $this->schema()->drop('invoices');
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

    public function test_it_should_automatically_generate_a_uuid_when_creating_a_model()
    {
        $invoice = UuidInvoice::create();

        $this->assertNotNull($invoice->uuid);
    }

    public function test_it_should_not_generate_a_uuid_if_one_has_already_been_set()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = UuidInvoice::create(['uuid' => $uuid->getBytes()]);

        $this->assertSame($uuid->getBytes(), $invoice->uuid);
        $this->assertSame($uuid->toString(), $invoice->uuid_text);
    }

    public function test_it_should_set_the_uuid_attribute_when_setting_the_uuid_text_mutator()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = UuidInvoice::create(['uuid_text' => $uuid->toString()]);

        $this->assertSame($uuid->getBytes(), $invoice->uuid);
        $this->assertSame($uuid->toString(), $invoice->uuid_text);
    }

    public function test_it_should_detect_a_custom_column()
    {
        $invoice = RenamedUuidColumnProject::create();

        $this->assertSame('guid', $invoice->getUuidColumn());
    }

    public function test_it_should_automatically_generate_a_uuid_when_creating_a_model_with_a_custom_column()
    {
        $invoice = RenamedUuidColumnProject::create();

        $this->assertNotNull($invoice->guid);
    }

    public function test_it_should_not_automatically_generate_a_uuid_if_one_has_already_been_set_with_a_custom_column()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = RenamedUuidColumnProject::create(['guid' => $uuid->getBytes()]);

        $this->assertSame($uuid->getBytes(), $invoice->guid);
        $this->assertSame($uuid->toString(), $invoice->uuid_text);
        $this->assertSame($uuid->toString(), $invoice->guid_text);
    }

    public function test_it_should_set_the_uuid_attribute_when_setting_the_uuid_text_mutator_and_using_a_custom_column()
    {
        $uuid = UuidGenerator::uuid4();

        $invoice = RenamedUuidColumnProject::create(['guid_text' => $uuid->toString()]);

        $this->assertSame($uuid->getBytes(), $invoice->guid);
        $this->assertSame($uuid->toString(), $invoice->guid_text);
    }
}

/**
 * Models
 */
class UuidInvoice extends Eloquent
{
    use BinaryUuid;

    public $timestamps = false;

    protected $table = 'invoices';

    protected $guarded = [];
}

class RenamedUuidColumnProject extends Eloquent
{
    use BinaryUuid;

    const UUID_COLUMN = 'guid';

    public $timestamps = false;

    protected $table = 'projects';

    protected $guarded = [];

    public function getGuidTextAttribute(): string
    {
        return $this->uuid_text;
    }

    public function setGuidTextAttribute(string $uuid)
    {
        $this->uuid_text = $uuid;
    }
}
