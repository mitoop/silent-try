<?php

namespace Mitoop\Silent;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Facade extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return new SilentTry();
    }
}
