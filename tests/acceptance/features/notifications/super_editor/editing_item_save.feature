Feature: Change Notifications
When an item is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0808 @change-notification @ui @super-editor
  Scenario: 0108-0814 Notification for item being edited when logged in as an Super-Editor
    Given I am logged in as an "Super-Editor"
    And I log a new "Super-User"
    When I create a framework
    And I add a Item
    And I select the item
    Then I edit the fields in a item
      | Human coding scheme   | QA Test Item         |
      | List enum in source   | 1                    |
      | Abbreviated statement | New Abb statement    |
      | Concept keywords      | reading              |
      | Concept keywords uri  | http://reading.com   |
      | Licence uri           | http://somewhere.com |
    Then I see a notification modified "Item"
    And I see the Item buttons enabled
    And I delete the framework