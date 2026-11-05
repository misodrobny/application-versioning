<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('the package does not depend on spatie')
    ->expect('DrobnyDev\ApplicationVersioning')
    ->not->toUse('Spatie');
