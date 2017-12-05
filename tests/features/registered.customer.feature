Feature: Registered Customer Test Suite
      MAGENTO 2.1

Scenario: I should be able to complete a 3D transaction using Frames integration
      Given I go to the backend of Checkout's plugin
      Given I enable 3D Secure
      Given I set the integration type to frames
      Given I save the backend settings
      Given I login the registered customer account
      Then I complete the order flow as a registered customer until the payment stage
      Then I choose Checkout as a payment option
      Then I complete Checkout Frames with a visa card
      Then I submit the order for the frames integration
      Then I complete the THREE D details
      Then I should see the success page
      Given I logout from the registered customer account

Scenario: I should be able to complete a non-3D transaction using Frames integration
      Given I go to the backend of Checkout's plugin
      Given I disable 3D Secure
      Given I set the integration type to frames
      Given I save the backend settings
      Given I login the registered customer account
      Then I complete the order flow as a registered customer until the payment stage
      Then I choose Checkout as a payment option
      Then I complete Checkout Frames with a visa card
      Then I submit the order for the frames integration
      Then I should see the success page
      Given I logout from the registered customer account

Scenario: I should be able to complete a 3D transaction using Hosted integration
      Given I go to the backend of Checkout's plugin
      Given I set the integration type to hosted
      Given I enable 3D Secure
      Given I save the backend settings
      Given I login the registered customer account
      Then I complete the order flow as a registered customer until the payment stage
      Then I choose Checkout as a payment option
      Then I submit the order for the hosted integration
      Then I complete Checkout Hosted with a visa card
      Then I complete the THREE D details
      Then I should see the success page
      Given I logout from the registered customer account

Scenario: I should be able to complete a non-3D transaction using Hosted integration
      Given I go to the backend of Checkout's plugin
      Given I disable 3D Secure
      Given I set the integration type to hosted
      Given I save the backend settings
      Given I login the registered customer account
      Then I complete the order flow as a registered customer until the payment stage
      Then I choose Checkout as a payment option
      Then I submit the order for the hosted integration
      Then I complete Checkout Hosted with a visa card
      Then I should see the success page
      Given I logout from the registered customer account

Scenario: I should be able to customise Hosted integration
      Given I go to the backend of Checkout's plugin
      Given I set the integration type to hosted
      Given I set the theme color
      Given I set the button label
      Given I save the backend settings
      Given I login the registered customer account
      Then I complete the order flow as a registered customer until the payment stage
      Then I choose Checkout as a payment option
      Then I submit the order for the hosted integration
      Then I should see a customised hosted page