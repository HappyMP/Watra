# Phase 13 — Tests Design

**Scope:** Behat setup + 2 feature files + API test + `bin/ci.sh`.

---

## What Already Exists (passing)

- `PermissionVoterTest` — unit, ✅
- `InterestServiceTest` — unit, ✅
- `BookingServiceTest` / `BookingRaceConditionTest` — unit, ✅
- `CheckoutFlowTest` / `CartControllerTest` — functional, ✅
- `RbacTest` — functional, ✅
- `ProductRepositoryTest` — functional, ✅

6 deprecation warnings are from vendor code — not fixable, not our concern.

---

## New: Behat Setup

**Driver:** `friends-of-behat/symfony-extension` (BrowserKit, no real browser needed).

**Config:** `behat.yml` at project root.

**Contexts:**
- `App\Tests\Behat\Context\AuthenticationContext` — login form submission steps
- `App\Tests\Behat\Context\ApiContext` — JSON API assertion steps
- `Behat\MinkExtension\Context\MinkContext` — built-in page navigation/assertion steps

**Test data:** Uses existing fixtures (admin@watra.pl / watra, jan.kowalski@example.pl / watra). Loaded once via `@BeforeSuite` hook in `AuthenticationContext`.

---

## Features

### `features/shop/events.feature`

```gherkin
Feature: Shop — events visible to guests and logged-in users

  Scenario: Guest visits shop homepage
    When I am on "/pl_PL/"
    Then the response status code should be 200

  Scenario: Events API returns published events
    When I send a GET request to "/api/v2/shop/events"
    Then the response status code should be 200
    And the JSON response has key "items"
    And the JSON response "total" is at least 1

  Scenario: Events list page is accessible
    When I am on "/pl_PL/taxons/wydarzenia"
    Then the response status code should be 200
```

### `features/admin/dashboard.feature`

```gherkin
Feature: Admin — dashboard shows WATRA metrics

  Scenario: Admin can log in and see WATRA dashboard
    Given I am logged in as admin "admin@watra.pl" with password "watra"
    When I am on "/admin/"
    Then the response status code should be 200
    And I should see "Opublikowane wydarzenia"

  Scenario: Admin can see events list
    Given I am logged in as admin "admin@watra.pl" with password "watra"
    When I am on "/admin/products/"
    Then the response status code should be 200
```

---

## New: API Test

`tests/Api/EventApiTest.php` — PHPUnit functional test for `GET /api/v2/shop/events`.

---

## New: `bin/ci.sh`

```bash
#!/bin/bash
set -e
vendor/bin/phpstan analyse
vendor/bin/ecs check
vendor/bin/phpunit
vendor/bin/behat --colors
```

---

## File Map

| File | Action |
|------|--------|
| `behat.yml` | Create |
| `features/shop/events.feature` | Create |
| `features/admin/dashboard.feature` | Create |
| `tests/Behat/Context/AuthenticationContext.php` | Create |
| `tests/Behat/Context/ApiContext.php` | Create |
| `tests/Api/EventApiTest.php` | Create |
| `bin/ci.sh` | Create |
| `docs/TASKS.md` | Modify |
