Feature: The framework is editable
  In order to confirm the framework can be edited
  As an organization-admin
  I need to edit a framework

  @incomplete @smoke @organization-admin @view-framework
  Scenario Outline: 1016-1344 An organization-admin can edit a framework
    Given I log in as a user with role "Admin"
    When I create a framework
    And I edit the fields
      | Title           | New Title           |
      | Creator         | New Creator         |
      | Official URI    | http://opensalt.com |
      | Publisher       | New Publisher       |
      | Version         | 2.0                 |
      | Description     | New Description     |
      | Adoption Status | Private Draft       |
      | Language        | fr                  |
      | Note            | New Note            |

    Then I should see the framework data
    And I delete the framework
