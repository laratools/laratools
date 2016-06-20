<?php

use Carbon\Carbon;
use Laratools\Eloquent\SupportsISO8601Dates;
use Illuminate\Database\Eloquent\Model as Eloquent;

class SupportsISO8601DatesTest extends PHPUnit_Framework_TestCase
{
    public function test_iso8601_date_only_value()
    {
        $model = new ISO8601Post();

        $model->published_at = '2016-01-01';

        $this->assertInstanceOf(Carbon::class, $model->published_at);
        $this->assertSame('2016-01-01', $model->published_at->format('Y-m-d'));
    }

    public function test_iso8601_date_with_time_and_offset_value()
    {
        $model = new ISO8601Post();

        $model->published_at = '2016-02-02T01:16:39+02:00';

        $this->assertInstanceOf(Carbon::class, $model->published_at);
        $this->assertSame('2016-02-02 01:16:39', $model->published_at->format('Y-m-d H:i:s'));
    }

    public function test_iso8601_date_with_time_value()
    {
        $model = new ISO8601Post();

        $model->published_at = '2016-03-03T02:32:51Z';

        $this->assertInstanceOf(Carbon::class, $model->published_at);
        $this->assertSame('2016-03-03 02:32:51', $model->published_at->format('Y-m-d H:i:s'));
    }

    public function test_iso8601_date_with_time_value_but_no_delimiters()
    {
        $model = new ISO8601Post();

        $model->published_at = '20160404T134212Z';

        $this->assertInstanceOf(Carbon::class, $model->published_at);
        $this->assertSame('2016-04-04 13:42:12', $model->published_at->format('Y-m-d H:i:s'));
    }

    public function test_iso8601_ordinal_date()
    {
        $model = new ISO8601Post();

        $model->published_at = '2016-150';

        $this->assertInstanceOf(Carbon::class, $model->published_at);
        $this->assertSame('2016-05-30', $model->published_at->format('Y-m-d'));
        $this->assertSame('2016 22 1', $model->published_at->format('o W N'));
        $this->assertSame('2016 150', $model->published_at->format('o z'));
    }

    public function test_iso8601_with_factional_seconds()
    {
        $model = new ISO8601Post();

        $model->published_at = '2016-05-02T13:42:11.78912Z';

        $this->assertInstanceOf(Carbon::class, $model->published_at);
        $this->assertSame('2016-05-02 13:42:11', $model->published_at->format('Y-m-d H:i:s'));
    }
}

/**
 * Models
 */
class ISO8601Post extends Eloquent
{
    use SupportsISO8601Dates;

    protected $dates = ['published_at'];
}
