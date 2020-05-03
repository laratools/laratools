<?php

namespace Laratools\Eloquent;

use PHPUnit\Framework\TestCase;
use Laratools\Eloquent\HasMetaInfo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class HasMetaInfoTest extends TestCase
{
    public function setUp(): void
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
     * Setup database schema
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('posts', function (Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->string('title');
        });

        $this->schema()->create('meta_information', function(Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->morphs('owner');
            $table->string('key')->index();
            $table->string('value');
            $table->boolean('is_encrypted')->default(false);
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->schema()->drop('posts');
        $this->schema()->drop('meta_information');
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

    protected function createPosts()
    {
        $post = MetaPost::create(['id' => 1, 'title' => 'This is post with meta data']);

        $post->meta()->create([
            'id'    => 1,
            'key'   => 'avatar',
            'value' => 'user_1.png',
        ]);

        $post->meta()->create([
            'id'    => 2,
            'key'   => 'stars',
            'value' => 38,
        ]);

        $post->meta()->create([
            'id'           => 3,
            'key'          => 'refresh_token',
            'value'        => '',
            'is_encrypted' => true,
        ]);

        $post->meta()->create([
            'id'           => 4,
            'key'          => 'access_token',
            'value'        => '12345',
            'is_encrypted' => true,
        ]);
    }

    public function test_has_meta_returns_correct_truthy_value()
    {
        $this->createPosts();

        $post = MetaPost::find(1);

        $this->assertTrue($post->hasMeta('avatar'));
        $this->assertFalse($post->hasMeta('non_existant_key'));
    }

    public function test_get_meta_returns_correct_value()
    {
        $this->createPosts();

        $post = MetaPost::find(1);

        $this->assertSame('user_1.png', $post->getMeta('avatar'));
        $this->assertNull($post->getMeta('non_existant_key'));
        $this->assertFalse($post->getMeta('non_existant_key', false));
    }

    public function test_set_meta_updates_the_value_and_also_creates_a_new_meta_information_entry_if_it_doesnt_exist()
    {
        $this->createPosts();

        $post = MetaPost::find(1);

        $post->setMeta('avatar', 'my_avatar.png');
        $post->setMeta('new_meta_key', 'hello');

        $this->assertSame('my_avatar.png', $post->getMeta('avatar'));
        $this->assertSame('hello', $post->getMeta('new_meta_key'));
    }
}

class MetaPost extends Eloquent
{
    use HasMetaInfo;

    protected $table = 'posts';

    protected $guarded = [];
}
