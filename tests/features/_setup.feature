Feature: Prepare Magento for Tests
      Disable SecretKey Encryption, Create a Product, Set Checkout Keys

Scenario: I setup Magento for tests
      Given I set the viewport and timeout
      Given I create a product
      Given I go to the backend of Checkout's plugin
      Given I set the sandbox keys
      Given I check the sandbox keys
      Given I save the backend settings
      Given I create an account
      Then I logout from the registered customer account