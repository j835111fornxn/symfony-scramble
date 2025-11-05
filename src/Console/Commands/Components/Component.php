<?php

namespace Dedoc\Scramble\Console\Commands\Components;

use Symfony\Component\Console\Style\SymfonyStyle;

interface Component
{
    public function render(SymfonyStyle $style): void;
}
