<?php

namespace Multt\Translation\Facades;

use Illuminate\Support\Facades\Facade;

class MulttTranslator extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'multt.translation.translator';
    }
}