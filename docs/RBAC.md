# WATRA — RBAC (Role-Based Access Control)

> **Status: placeholder.** Pełna dokumentacja powstanie w Fazie 14 ([TASKS.md](TASKS.md) task 14.4).

## TOC (planowany)

1. **Architektura** — opis komponentów (`Permission` enum, `AdministrationRole` entity, `PermissionVoter`).
2. **Lista permissionów** — pełna referencja, pogrupowana po zasobach.
3. **Domyślne role** — Super Admin, Editor, Marketer, Front Desk: jakie mają permissiony i kiedy ich używamy.
4. **Jak dodać nowy permission** — krok po kroku (enum → migracja → fixture → użycie w controllerze/Twigu/API).
5. **Jak stworzyć nową rolę** — przez panel admin lub fixture.
6. **Jak zabezpieczyć nowy endpoint/action** — `#[IsGranted]` w controllerze, `security:` w API Platform Resource, `is_granted()` w Twig.
7. **Testowanie** — wzorce do `PermissionVoterTest` i `RbacTest`.
8. **Anty-wzorce** — czego NIE robić (np. `ROLE_ADMIN` zamiast permission, hard-codowane permission stringi).

## Przegląd architektury (skrócony)

```
User (AdminUser)
  └─ has many → AdministrationRole
                   └─ has json[] permissions: ["event:create", "booking:cancel", ...]
                   └─ has bool isSuperAdmin (bypass voter)

PermissionVoter (Symfony Voter)
  - supports("event:edit") = true
  - voteOnAttribute(): user has any role with isSuperAdmin = true → grant
                      | user has any role with permission "event:edit" → grant
                      | else → deny
```

## Quick reference

```php
// Controller
#[IsGranted(Permission::EVENT_CREATE->value)]
public function newEvent(): Response { ... }

// API Platform Resource
#[Post(security: "is_granted('event:create')")]

// Twig
{% if is_granted('event:create') %}
    <a href="{{ path('admin_event_new') }}">Dodaj wydarzenie</a>
{% endif %}

// Programowo
if ($this->security->isGranted('booking:cancel')) { ... }
```

---

*Pełna dokumentacja zostanie uzupełniona po implementacji RBAC w Fazie 6.*
