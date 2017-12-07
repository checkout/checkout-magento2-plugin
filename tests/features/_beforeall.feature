Feature: Setup Magento
      Keys, Url's

Scenario: I should be able to disable magento's URL incription and set the plugin keys
      Given I set the viewport and timeout
      Given I disable the url secret key encryption
      Given I update the stock for my test item
      Given I go to the backend of Checkout's plugin
      Given I set the sandbox keys
      Given I save the backend settings
      Given I create an account
      Given I logout from the registered customer account