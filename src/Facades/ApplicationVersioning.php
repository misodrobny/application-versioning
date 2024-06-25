<?php

namespace Michal Drobny\ApplicationVersioning\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Michal Drobny\ApplicationVersioning\ApplicationVersioning
 */
class ApplicationVersioning extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Michal Drobny\ApplicationVersioning\ApplicationVersioning::class;
    }
}
