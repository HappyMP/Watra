<?php

declare(strict_types=1);

namespace App\Security;

enum Permission: string
{
    // Events (Product)
    case EVENT_INDEX = 'event:index';
    case EVENT_SHOW = 'event:show';
    case EVENT_CREATE = 'event:create';
    case EVENT_UPDATE = 'event:update';
    case EVENT_DELETE = 'event:delete';

    // Bookings (Order)
    case BOOKING_INDEX = 'booking:index';
    case BOOKING_SHOW = 'booking:show';
    case BOOKING_UPDATE = 'booking:update';
    case BOOKING_EXPORT = 'booking:export';

    // Attendees (Customer)
    case ATTENDEE_INDEX = 'attendee:index';
    case ATTENDEE_SHOW = 'attendee:show';
    case ATTENDEE_CREATE = 'attendee:create';
    case ATTENDEE_UPDATE = 'attendee:update';
    case ATTENDEE_DELETE = 'attendee:delete';

    // Tags (Taxon)
    case TAG_INDEX = 'tag:index';
    case TAG_CREATE = 'tag:create';
    case TAG_UPDATE = 'tag:update';
    case TAG_DELETE = 'tag:delete';

    // Cities & Venues
    case CITY_MANAGE = 'city:manage';
    case VENUE_MANAGE = 'venue:manage';

    // Admin Users
    case ADMIN_USER_INDEX = 'admin_user:index';
    case ADMIN_USER_CREATE = 'admin_user:create';
    case ADMIN_USER_UPDATE = 'admin_user:update';
    case ADMIN_USER_DELETE = 'admin_user:delete';

    // Administration Roles
    case ROLE_MANAGE = 'role:manage';

    // Reports / Dashboard
    case DASHBOARD_ACCESS = 'dashboard:access';

    /** @return string[] */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns all Permission cases whose value starts with "$resource:".
     *
     * @return self[]
     */
    public static function group(string $resource): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $p): bool => str_starts_with($p->value, $resource . ':'),
        ));
    }

    /** Returns all resource prefixes that exist in the enum. */
    public static function resources(): array
    {
        $resources = [];
        foreach (self::cases() as $case) {
            $prefix = explode(':', $case->value)[0];
            $resources[$prefix] = true;
        }

        return array_keys($resources);
    }
}
