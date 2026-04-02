Feature: wcsr restore command
  As a site administrator
  I want to restore subscription data from a previously created backup
  So that I can undo changes if something went wrong

  Background:
    Given a WP installation
    And the WooCommerce stubs are loaded

  Scenario: Restore from a backup file
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And I run `wp wcsr create --id={SUBSCRIPTION_ID}`
    And the backup file should exist in wp-content

    When I run `wp wcsr restore --file={BACKUP_FILE}`

    Then STDOUT should contain:
      """
      Success: Successfully restored from given file.
      """

  Scenario: Restore and delete backup file
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And I run `wp wcsr create --id={SUBSCRIPTION_ID}`
    And the backup file should exist in wp-content

    When I run `wp wcsr restore --file={BACKUP_FILE} --delete`

    Then STDOUT should contain:
      """
      Success: Successfully restored from given file.
      """
    And STDOUT should contain:
      """
      Deleted backup file:
      """
    And the backup file should not exist in wp-content

  Scenario: Restore fails with missing file argument
    When I try `wp wcsr restore`

    Then the return code should not be 0

  Scenario: Restore fails with non-existent file
    When I try `wp wcsr restore --file=nonexistent_file.sql`

    Then STDERR should contain:
      """
      Backup file not found
      """
    And the return code should not be 0

  Scenario: Full round-trip backup then update then restore
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"

    # Backup original state
    And I run `wp wcsr create --id={SUBSCRIPTION_ID}`
    And the backup file should exist in wp-content

    # Change product price and update subscription
    And the product "{PRODUCT_ID}" price is changed to "20.00"
    And I run `wp wcsr update --id={SUBSCRIPTION_ID}`
    And the subscription "{SUBSCRIPTION_ID}" should have line item total "20"

    # Restore from backup
    When I run `wp wcsr restore --file={BACKUP_FILE}`

    Then STDOUT should contain:
      """
      Success: Successfully restored from given file.
      """
