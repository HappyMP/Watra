# WATRA — RBAC (Role-Based Access Control)

## Architektura

System uprawnień składa się z trzech komponentów:

1. **`App\Security\Permission`** — PHP backed enum z ~30 wartościami (np. `event:create`, `booking:index`). Każda wartość to jedna atomowa operacja.
2. **`App\Entity\Admin\AdministrationRole`** — encja Doctrine. Przechowuje `name`, `isSuperAdmin: bool` i `permissions: array<string>` (JSON w DB). Admin może mieć wiele ról.
3. **`App\Security\Voter\PermissionVoter`** — extends `Symfony\Voter`. Dla każdego `is_granted('event:create')` sprawdza czy user ma rolę z tym permission. Super admin zawsze GRANT.

Routing → permission mapping: `App\Security\AdminRoutePermissionMap` — static array route name → Permission enum value. Sprawdzany przez `App\EventSubscriber\AdminRoutePermissionSubscriber` na `kernel.request`.

## Lista permissionów

| Permission | Opis |
|-----------|------|
| `event:index` | Przeglądanie listy wydarzeń |
| `event:show` | Podgląd szczegółów wydarzenia |
| `event:create` | Tworzenie nowych wydarzeń |
| `event:update` | Edycja istniejących wydarzeń |
| `event:delete` | Usuwanie wydarzeń |
| `booking:index` | Przeglądanie listy rezerwacji |
| `booking:show` | Podgląd rezerwacji |
| `booking:update` | Edycja rezerwacji |
| `booking:export` | Eksport CSV uczestników |
| `attendee:index` | Przeglądanie uczestników |
| `attendee:show` | Podgląd uczestnika |
| `attendee:create` | Tworzenie uczestników |
| `attendee:update` | Edycja uczestników |
| `attendee:delete` | Usuwanie uczestników |
| `tag:index` | Przeglądanie tagów |
| `tag:create` | Tworzenie tagów |
| `tag:update` | Edycja tagów |
| `tag:delete` | Usuwanie tagów |
| `city:manage` | Zarządzanie miastami |
| `venue:manage` | Zarządzanie lokalizacjami |
| `admin_user:index` | Przeglądanie adminów |
| `admin_user:create` | Tworzenie adminów |
| `admin_user:update` | Edycja adminów |
| `admin_user:delete` | Usuwanie adminów |
| `role:manage` | Zarządzanie rolami |
| `dashboard:access` | Dostęp do panelu |

## Domyślne role (fixtures)

| Rola | isSuperAdmin | Uprawnienia |
|------|-------------|------------|
| **Super Admin** | `true` | Wszystko (ignoruje listę permissionów) |
| **Editor** | `false` | Pełny CRUD na wydarzeniach i tagach |
| **Marketer** | `false` | Podgląd wydarzeń, uczestników, rezerwacji |
| **Front Desk** | `false` | Rezerwacje (index/show/update), Uczestnicy (index/show), Dashboard |

## Jak dodać nowy permission

1. Dodaj case do `src/Security/Permission.php`:
   ```php
   case MY_PERMISSION = 'resource:action';
   ```
2. Dodaj go do `Permission::group('resource')` jeśli istnieje, lub stwórz nową grupę w `groups()`.
3. W `src/Security/AdminRoutePermissionMap.php` przypisz route name → nowy Permission.
4. W templates użyj `{% if has_permission('resource:action') %}` do warunkowego renderowania.
5. Dodaj do odpowiednich ról w `src/Fixture/AdministrationRoleFixture.php`.

## Jak stworzyć nową rolę

1. **W admin panelu:** `/admin/administration-roles/new` — formularz z multi-checkboxem permissionów.
2. **Przez fixture:** w `AdministrationRoleFixture::load()` dodaj `createRole('Nazwa', false, [Permission::X->value, ...])`.
3. **Przypisz rolę do admina:** `/admin/admin-users/{id}/edit`.

## Twig — sprawdzanie uprawnień

```twig
{% if has_permission('event:create') %}
    <a href="{{ path('sylius_admin_product_create') }}">Nowe wydarzenie</a>
{% endif %}
```

Funkcja Twig `has_permission()` zdefiniowana w `src/Twig/Extension/PermissionExtension.php`.

## PHP — sprawdzanie uprawnień

```php
// W kontrolerze
$this->denyAccessUnlessGranted(Permission::EVENT_CREATE->value);

// Przez atrybut
#[IsGranted(Permission::EVENT_CREATE->value)]
public function create(): Response { ... }
```
