<?php

namespace Fjord\Support\Facades;

class FjordLang extends FjordFacade
{
    protected static function getFacadeAccessor()
    {
        return 'translator';
    }
}
