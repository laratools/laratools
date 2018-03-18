<?php

namespace Laratools\Database\Schema\Grammar;

use Illuminate\Database\Schema\Grammars\MySqlGrammar as IlluminateMySqlGrammar;

class MySqlGrammar extends IlluminateMySqlGrammar
{
    protected function typeBinaryUuid()
    {
        return 'binary(16)';
    }
}
