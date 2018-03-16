<?php

namespace Laratools\Database\Schema\Grammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as IlluminateSQLiteGrammar;

class SQLiteGrammar extends IlluminateSQLiteGrammar
{
    protected function typeBinaryUuid()
    {
        return 'binary(16)';
    }
}
