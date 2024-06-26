<?php

namespace DrobnyDev\ApplicationVersioning\Facades;

use Illuminate\Support\Facades\Facade;

class ApplicationVersioning extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DrobnyDev\ApplicationVersioning\ApplicationVersioning::class;
    }
}
