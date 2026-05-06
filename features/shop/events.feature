Feature: Shop — events visible to guests

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
