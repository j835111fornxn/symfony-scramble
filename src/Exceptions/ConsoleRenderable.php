<?php

namespace Dedoc\Scramble\Exceptions;

use Symfony\Component\Console\Style\SymfonyStyle;

interface ConsoleRenderable
{
    public function renderInConsole(SymfonyStyle $outputStyle): void;
}
