<?php

namespace Dedoc\Scramble\Tests\Fixtures\Laravel\Models;

enum Role: string
{
    case Admin = 'admin';
    case TeamLead = 'team_lead';
    case Developer = 'developer';
}
