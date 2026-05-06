Feature: Admin — dashboard shows WATRA metrics

  Scenario: Admin can access dashboard with WATRA metrics
    Given I am logged in as admin "admin@watra.pl" with password "watra"
    When I am on "/admin/"
    Then the response status code should be 200
    And I should see "Opublikowane wydarzenia"

  Scenario: Admin can access events list
    Given I am logged in as admin "admin@watra.pl" with password "watra"
    When I am on "/admin/products/"
    Then the response status code should be 200
