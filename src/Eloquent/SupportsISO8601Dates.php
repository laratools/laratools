<?php

namespace Laratools\Eloquent;

use Carbon\Carbon;

trait SupportsISO8601Dates
{
    protected $iso8601Dates = [
        'Y-m-d',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s\Z',
        'Ymd\THis\Z',
        'o-\WW',
        'o-\WW-N',
        'Y-z',

        // Format denoted by the ES2015 specification http://www.ecma-international.org/ecma-262/6.0/#sec-date.prototype.toisostring
        'Y-m-d\TH:i:s\.u\Z',
    ];

    protected function asDateTime($value)
    {
        foreach($this->iso8601Dates as $format)
        {
            $parsed = date_parse_from_format($format, $value);

            if ($parsed['error_count'] === 0 && $parsed['warning_count'] === 0)
            {
                $value = Carbon::createFromFormat($format, $value);

                break;
            }
        }

        return parent::asDateTime($value);
    }
}
