Feature: Update subscription prices

  Scenario: Dry runs report price changes without persisting them
    Given a WP install
    And I am in the "{PACKAGE_DIR}" directory

    When I run `wp eval-file {PACKAGE_DIR}/features/bootstrap/install-test-stubs.php`
    Then STDOUT should contain:
      """
      Installed test stubs.
      """

    When I run `wp eval-file {PACKAGE_DIR}/features/bootstrap/seed-update-command-data.php`
    Then STDOUT should contain:
      """
      Seeded update command test data.
      """

    When I run `wp wcsr update --id=$(wp option get wcsr_test_active_subscription_id) --dry-run --format=json`
    Then STDOUT should contain:
      """
      "subscription_id"
      """
    And STDOUT should contain:
      """
      "old_price":"15"
      """
    And STDOUT should contain:
      """
      "new_price":"25"
      """

    When I run `wp eval 'echo ((array) get_post_meta( (int) get_option( "wcsr_test_active_subscription_id" ), "_wcsr_items", true ))[0]["subtotal"];'`
    Then STDOUT should contain:
      """
      15
      """

  Scenario: Status filters update only matching subscriptions
    Given a WP install
    And I am in the "{PACKAGE_DIR}" directory

    When I run `wp eval-file {PACKAGE_DIR}/features/bootstrap/install-test-stubs.php`
    Then STDOUT should contain:
      """
      Installed test stubs.
      """

    When I run `wp eval-file {PACKAGE_DIR}/features/bootstrap/seed-update-command-data.php`
    Then STDOUT should contain:
      """
      Seeded update command test data.
      """

    When I run `wp wcsr update --status=active --format=json`
    Then STDOUT should contain:
      """
      "subscription_id"
      """
    And STDOUT should contain:
      """
      "old_price":"15"
      """
    And STDOUT should contain:
      """
      "new_price":"25"
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
