<?php

namespace Dedoc\Scramble\Tests\Fixtures\Laravel\Models;

enum Status: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
