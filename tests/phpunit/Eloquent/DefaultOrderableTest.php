<?php

use Laratools\Eloquent\DefaultOrderable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DefaultOrderableTest extends PHPUnit_Framework_TestCase
{
    public function test_default_orderable_sets_correct_order_by_clause()
    {
        $model = new OrderablePost();
        $query = $model->newQuery()->toBase();

        $order = $query->orders[0];

        $this->assertSame('posts.published_at', $order['column']);
        $this->assertSame('desc', $order['direction']);
    }

    public function test_multiple_orderable_sets_correct_order_by_clauses()
    {
        $model = new MultipleOrderableUser();
        $query = $model->newQuery()->toBase();

        $firstOrder = $query->orders[0];
        $secondOrder = $query->orders[1];

        $this->assertSame('users.name', $firstOrder['column']);
        $this->assertSame('desc', $firstOrder['direction']);
        $this->assertSame('users.dob', $secondOrder['column']);
        $this->assertSame('asc', $secondOrder['direction']);
    }
}

class OrderablePost extends Eloquent
{
    use DefaultOrderable;

    protected $table = 'posts';

    protected $guarded = [];

    public function getDefaultOrderableColumns()
    {
        return [
            'published_at' => 'DESC',
        ];
    }
}

class MultipleOrderableUser extends Eloquent
{
    use DefaultOrderable;

    protected $table = 'users';

    protected $guarded = [];

    public function getDefaultOrderableColumns()
    {
        return [
            'name' => 'DESC',
            'dob' => 'ASC',
        ];
    }
}
