Feature: wcsr create (backup) command
  As a site administrator
  I want to create SQL backups of subscription data before making changes
  So that I can restore them if something goes wrong

  Background:
    Given a WP installation
    And the WooCommerce stubs are loaded

  Scenario: Create a backup of all subscriptions
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"

    When I run `wp wcsr create`

    Then STDOUT should contain:
      """
      Successfully created dump of affected rows at:
      """
    And STDOUT should contain:
      """
      wcsr_backup_
      """
    And the backup file should exist in wp-content
    And the backup file should contain SQL for subscription "{SUBSCRIPTION_ID}"

  Scenario: Create a backup for a specific subscription by ID
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"

    When I run `wp wcsr create --id={SUBSCRIPTION_ID}`

    Then STDOUT should contain:
      """
      Successfully created dump of affected rows at:
      """
    And the backup file should exist in wp-content
    And the backup file should contain SQL for subscription "{SUBSCRIPTION_ID}"

  Scenario: Create a backup filtered by active status
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"

    When I run `wp wcsr create --status=active`

    Then STDOUT should contain:
      """
      Successfully created dump of affected rows at:
      """
    And the backup file should exist in wp-content

  Scenario: Create a backup with invalid status fails
    When I try `wp wcsr create --status=invalid`

    Then STDERR should contain:
      """
      Invalid subscription status
      """
    And the return code should not be 0

  Scenario: Backup with no matching subscriptions fails
    When I try `wp wcsr create --status=cancelled`

    Then STDERR should contain:
      """
      No subscriptions found
      """
    And the return code should not be 0

  Scenario: Legacy backup alias works
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"

    When I run `wp wcsr backup`

    Then STDOUT should contain:
      """
      Successfully created dump of affected rows at:
      """
    And the backup file should exist in wp-content

  Scenario: Backup file contains INSERT statements
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"

    When I run `wp wcsr create --id={SUBSCRIPTION_ID}`

    Then the backup file should exist in wp-content
    And the backup file should contain SQL for subscription "{SUBSCRIPTION_ID}"
