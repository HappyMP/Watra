Feature: Shop — global layout (navbar + footer)

  Scenario: Navbar is present on homepage
    When I am on "/pl_PL/"
    Then the response status code should be 200
    And the response should contain "watra-navbar"

  Scenario: Footer is present on homepage
    When I am on "/pl_PL/"
    Then the response should contain "watra-footer"

  Scenario: Navbar is present on events list
    When I am on "/pl_PL/taxons/wydarzenia"
    Then the response status code should be 200
    And the response should contain "watra-navbar"
    And the response should contain "watra-footer"

  Scenario: Footer tagline text is present
    When I am on "/pl_PL/"
    Then the response should contain "WATRA"
