<?php

namespace Dedoc\Scramble\Console\Commands\Components;

use NunoMaduro\Collision\Highlighter;
use Symfony\Component\Console\Style\SymfonyStyle;

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
