<?php

namespace Dedoc\Scramble\Console\Commands\Components;

use Symfony\Component\Console\Style\SymfonyStyle;
use NunoMaduro\Collision\Highlighter;

class Code implements Component
{
    public function __construct(
        public string $filePath,
        public int $line,
    ) {}

    public function render(SymfonyStyle $style): void
    {
        $code = (new Highlighter)->highlight(file_get_contents($this->filePath), $this->line);

        $style->writeln($code);
    }
}
