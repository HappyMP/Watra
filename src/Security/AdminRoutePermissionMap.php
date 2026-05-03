<?php

declare(strict_types=1);

namespace App\Security;

final class AdminRoutePermissionMap
{
    private const MAP = [
        // Events (Product)
        'sylius_admin_product_index'          => Permission::EVENT_INDEX,
        'sylius_admin_product_create'         => Permission::EVENT_CREATE,
        'sylius_admin_product_update'         => Permission::EVENT_UPDATE,
        'sylius_admin_product_delete'         => Permission::EVENT_DELETE,
        'sylius_admin_product_show'           => Permission::EVENT_SHOW,
        'sylius_admin_product_variant_index'  => Permission::EVENT_INDEX,
        'sylius_admin_product_variant_create' => Permission::EVENT_UPDATE,
        'sylius_admin_product_variant_update' => Permission::EVENT_UPDATE,
        'sylius_admin_product_variant_delete' => Permission::EVENT_DELETE,

        // Bookings (Order)
        'sylius_admin_order_index'  => Permission::BOOKING_INDEX,
        'sylius_admin_order_show'   => Permission::BOOKING_SHOW,
        'sylius_admin_order_update' => Permission::BOOKING_UPDATE,

        // Attendees (Customer)
        'sylius_admin_customer_index'  => Permission::ATTENDEE_INDEX,
        'sylius_admin_customer_show'   => Permission::ATTENDEE_SHOW,
        'sylius_admin_customer_create' => Permission::ATTENDEE_CREATE,
        'sylius_admin_customer_update' => Permission::ATTENDEE_UPDATE,
        'sylius_admin_customer_delete' => Permission::ATTENDEE_DELETE,

        // Tags (Taxon)
        'sylius_admin_taxon_index'  => Permission::TAG_INDEX,
        'sylius_admin_taxon_create' => Permission::TAG_CREATE,
        'sylius_admin_taxon_update' => Permission::TAG_UPDATE,
        'sylius_admin_taxon_delete' => Permission::TAG_DELETE,

        // Cities & Venues
        'app_admin_city_index'   => Permission::CITY_MANAGE,
        'app_admin_city_create'  => Permission::CITY_MANAGE,
        'app_admin_city_update'  => Permission::CITY_MANAGE,
        'app_admin_city_delete'  => Permission::CITY_MANAGE,
        'app_admin_venue_index'  => Permission::VENUE_MANAGE,
        'app_admin_venue_create' => Permission::VENUE_MANAGE,
        'app_admin_venue_update' => Permission::VENUE_MANAGE,
        'app_admin_venue_delete' => Permission::VENUE_MANAGE,

        // Administration Roles
        'app_admin_administration_role_index'  => Permission::ROLE_MANAGE,
        'app_admin_administration_role_create' => Permission::ROLE_MANAGE,
        'app_admin_administration_role_update' => Permission::ROLE_MANAGE,
        'app_admin_administration_role_delete' => Permission::ROLE_MANAGE,

        // Admin Users
        'sylius_admin_admin_user_index'  => Permission::ADMIN_USER_INDEX,
        'sylius_admin_admin_user_create' => Permission::ADMIN_USER_CREATE,
        'sylius_admin_admin_user_update' => Permission::ADMIN_USER_UPDATE,
        'sylius_admin_admin_user_delete' => Permission::ADMIN_USER_DELETE,
    ];

    public function getRequiredPermission(string $routeName): ?Permission
    {
        return self::MAP[$routeName] ?? null;
    }
}
