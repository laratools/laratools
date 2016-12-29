<?php

namespace Laratools\Eloquent;

use PHPUnit_Framework_TestCase;
use Carbon\Carbon;
use Laratools\Eloquent\Archivable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ArchivableTest extends PHPUnit_Framework_TestCase
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
            $table->timestamp('archived_at')->nullable();
        });

        $this->schema()->create('comments', function(Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->string('body');
            $table->timestamp('hidden_at')->nullable();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('posts');
        $this->schema()->drop('comments');
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
        $aPost = ArchivablePost::create(['id' => 1, 'title' => 'This is post A']);
        $bPost = ArchivablePost::create(['id' => 2, 'title' => 'This is post B']);

        $aPost->sendToArchive();
    }

    public function test_archived_models_are_not_retrieved()
    {
        $this->createPosts();

        $posts = ArchivablePost::all();

        $this->assertCount(1, $posts);
        $this->assertEquals(2, $posts->first()->id);
        $this->assertNull(ArchivablePost::find(1));
    }

    public function test_with_archived_returns_all_models()
    {
        $this->createPosts();

        $this->assertCount(2, ArchivablePost::withArchived()->get());
        $this->assertInstanceOf(Eloquent::class, ArchivablePost::withArchived()->find(1));
    }

    public function test_only_archived_returns_just_archived_models()
    {
        $this->createPosts();

        $posts = ArchivablePost::onlyArchived()->get();

        $this->assertCount(1, $posts);
        $this->assertEquals(1, $posts->first()->id);
        $this->assertNull(ArchivablePost::onlyArchived()->find(2));
    }

    public function test_send_to_archive_sets_archived_at_column()
    {
        $this->createPosts();

        $this->assertInstanceOf(Carbon::class, ArchivablePost::withArchived()->find(1)->archived_at);
        $this->assertNull(ArchivablePost::find(2)->archived_at);
    }

    public function test_restore_from_archive_unarchives_model()
    {
        $this->createPosts();

        $aPost = ArchivablePost::withArchived()->find(1);

        $this->assertTrue($aPost->isArchived());

        $aPost->restoreFromArchive();

        $posts = ArchivablePost::all();

        $this->assertCount(2, $posts);
        $this->assertNull($posts->find(1)->archived_at);
        $this->assertNull($posts->find(2)->archived_at);
    }

    public function test_send_to_archive_sets_column_when_not_using_the_standard_column_name()
    {
        $aComment = RenamedArchivableColumnComment::create(['id' => 1, 'body' => 'This is comment A']);
        $bComment = RenamedArchivableColumnComment::create(['id' => 2, 'body' => 'This is comment B']);

        $aComment->sendToArchive();

        $this->assertNull(RenamedArchivableColumnComment::find(2)->hidden_at);
        $this->assertNull($bComment->hidden_at);

        $this->assertInstanceOf(Carbon::class, RenamedArchivableColumnComment::withArchived()->find(1)->hidden_at);
        $this->assertInstanceOf(Carbon::class, $aComment->hidden_at);
    }
}

/**
 * Models
 */
class ArchivablePost extends Eloquent
{
    use Archivable;

    protected $dates = ['archived_at'];

    protected $table = 'posts';

    protected $guarded = [];
}

class RenamedArchivableColumnComment extends Eloquent
{
    use Archivable;

    const ARCHIVED_AT_COLUMN = 'hidden_at';

    protected $dates = ['hidden_at'];

    protected $table = 'comments';

    protected $guarded = [];
}