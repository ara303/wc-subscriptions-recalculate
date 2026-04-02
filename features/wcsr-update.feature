Feature: wcsr update command
  As a site administrator
  I want to bulk-update WooCommerce Subscription prices to match current product prices
  So that subscriptions stay in sync when I change product pricing

  Background:
    Given a WP installation
    And the WooCommerce stubs are loaded

  Scenario: Update a single subscription when product price has changed
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "15.00"

    When I run `wp wcsr update --id={SUBSCRIPTION_ID}`

    Then STDOUT should contain:
      """
      subscription_id
      """
    And STDOUT should contain:
      """
      15
      """
    And STDOUT should contain:
      """
      Success: Completed.
      """
    And the subscription "{SUBSCRIPTION_ID}" should have line item total "15"

  Scenario: Dry run does not write changes to database
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "20.00"

    When I run `wp wcsr update --id={SUBSCRIPTION_ID} --dry-run`

    Then STDOUT should contain:
      """
      --dry-run flag means no changes were made
      """
    And the subscription "{SUBSCRIPTION_ID}" should still have line item total "10.00"

  Scenario: No output rows when prices have not changed
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"

    When I run `wp wcsr update --id={SUBSCRIPTION_ID}`

    Then STDOUT should contain:
      """
      Success: Completed.
      """

  Scenario: Update all subscriptions at once
    Given a product "Monthly Widget" with price "10.00"
    And 3 active subscriptions exist for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "12.00"

    When I run `wp wcsr update`

    Then STDOUT should contain:
      """
      12
      """
    And STDOUT should contain:
      """
      Success: Completed.
      """

  Scenario: Filter update by active status
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "18.00"

    When I run `wp wcsr update --status=active`

    Then STDOUT should contain:
      """
      18
      """
    And STDOUT should contain:
      """
      Success: Completed.
      """

  Scenario: Filtering by cancelled status skips active subscriptions
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "25.00"

    When I try `wp wcsr update --status=cancelled`

    Then the return code should not be 0

  Scenario: Output in JSON format
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "15.00"

    When I run `wp wcsr update --id={SUBSCRIPTION_ID} --format=json`

    Then STDOUT should contain:
      """
      "old_price"
      """
    And STDOUT should contain:
      """
      "new_price"
      """

  Scenario: Output in CSV format
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "15.00"

    When I run `wp wcsr update --id={SUBSCRIPTION_ID} --format=csv`

    Then STDOUT should contain:
      """
      subscription_id,old_price,new_price
      """

  Scenario: Legacy recalculate alias works
    Given a product "Monthly Widget" with price "10.00"
    And an active subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "15.00"

    When I run `wp wcsr recalculate --id={SUBSCRIPTION_ID}`

    Then STDOUT should contain:
      """
      Success: Completed.
      """
    And the subscription "{SUBSCRIPTION_ID}" should have line item total "15"

  Scenario: Update subscription with on-hold status
    Given a product "Monthly Widget" with price "10.00"
    And a "on-hold" subscription exists for product "{PRODUCT_ID}" with price "10.00"
    And the product "{PRODUCT_ID}" price is changed to "22.00"

    When I run `wp wcsr update --status=on-hold`

    Then STDOUT should contain:
      """
      22
      """
    And STDOUT should contain:
      """
      Success: Completed.
      """
