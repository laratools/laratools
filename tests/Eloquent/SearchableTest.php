<?php

namespace Laratools\Eloquent;

use PHPUnit_Framework_TestCase;
use Laratools\Eloquent\Searchable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class SearchableTest extends PHPUnit_Framework_TestCase
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
        });

        $this->schema()->create('comments', function(Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('post_id');
            $table->text('body');
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

    protected function createDbData()
    {
        $aPost = SearchablePost::create(['id' => 1, 'title' => 'A post by Stephen Fry']);
        $bPost = SearchablePost::create(['id' => 2, 'title' => 'A post by Alan Davis']);

        $aPost->comments()->create(['id' => 1, 'body' => 'This is a comment by Sandi Toksvig']);
        $aPost->comments()->create(['id' => 2, 'body' => 'This is a comment by Bill Bailey']);

        $bPost->comments()->create(['id' => 3, 'body' => 'This is a comment by Jo Brand']);
        $bPost->comments()->create(['id' => 4, 'body' => 'This is a comment by Phill Jupitus']);
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

    public function test_searchable_on_a_models_own_attributes()
    {
        $this->createDbData();

        $posts = SearchablePost::search('stephen')->get();

        $this->assertCount(1, $posts);
        $this->assertSame('A post by Stephen Fry', $posts->first()->title);
    }

    public function test_searchable_on_a_models_relations()
    {
        $this->createDbData();

        $posts = SearchablePost::search('jo brand')->get();

        $this->assertCount(1, $posts);
        $this->assertSame('A post by Alan Davis', $posts->first()->title);
    }

}

class SearchablePost extends Eloquent
{
    use Searchable;

    protected $table = 'posts';

    protected $guarded = [];

    protected $searchable = ['title', 'comments.body'];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}

class Comment extends Eloquent
{
    protected $table = 'comments';

    protected $guarded = [];
}