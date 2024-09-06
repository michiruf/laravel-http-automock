<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

// We do not want to use base carbon, but the variant from illuminate, since its easily mocked
arch('it will not use base carbon')
    ->expect('\\Carbon\\Carbon')
    ->not->toBeUsed();
