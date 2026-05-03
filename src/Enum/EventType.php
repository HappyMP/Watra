<?php

declare(strict_types=1);

namespace App\Enum;

enum EventType: string
{
    case WORKSHOP = 'workshop';
    case MEETUP = 'meetup';
    case PARTY = 'party';
    case CONFERENCE = 'conference';
    case OTHER = 'other';
}
