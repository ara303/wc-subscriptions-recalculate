Feature: Update subscription prices

  Scenario: Status filters update only matching subscriptions
    Given a WP install

    When I run `wp eval-file {PROJECT_DIR}/features/bootstrap/install-test-stubs.php`
    Then STDOUT should contain:
      """
      Installed test stubs.
      """

    When I run `wp eval-file {PROJECT_DIR}/features/bootstrap/seed-update-command-data.php`
    Then STDOUT should contain:
      """
      Seeded update command test data.
      """

    When I run `wp wcsr update --status=active --format=csv`
    Then STDOUT should contain:
      """
      subscription_id,old_price,new_price
      """
    And STDOUT should contain:
      """
      ,15,25
      """

    When I run `wp eval 'echo ((array) get_post_meta( (int) get_option( "wcsr_test_active_subscription_id" ), "_wcsr_items", true ))[0]["subtotal"];'`
    Then STDOUT should contain:
      """
      25
      """

    When I run `wp eval 'echo ((array) get_post_meta( (int) get_option( "wcsr_test_cancelled_subscription_id" ), "_wcsr_items", true ))[0]["subtotal"];'`
    Then STDOUT should contain:
      """
      8
      """
