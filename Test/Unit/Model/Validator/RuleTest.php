<?php

namespace CheckoutCom\Magento2\Test\Unit\Model\Validator;

use CheckoutCom\Magento2\Model\Validator\Rule;
use PHPUnit_Framework_TestCase;

class RuleTest extends PHPUnit_Framework_TestCase {

    public function testIsValid() {
        $testData = [
            'testValue' => true,
        ];

        $rule = new Rule('test name', function(array $test) {
            return $test['testValue'];
        });

        static::assertEquals(true, $rule->isValid($testData));
    }

    public function testIsNotValid() {
        $testData = [
            'testValue' => false,
        ];

        $rule = new Rule('test name', function(array $test) {
            return $test['testValue'];
        });

        static::assertEquals(false, $rule->isValid($testData));
    }

    public function testErrorMessage() {
        $rule = new Rule('test name', function(array $test) {
            return $test['testValue'];
        }, 'Invalid value');

        static::assertEquals('Invalid value', $rule->getErrorMessage());
    }

    public function testSetErrorMessage() {
        $testData = [
            'testValue' => false,
        ];

        $rule = new Rule('test name', function(array $test, Rule $rule) {
            if( ! $test['testValue']) {
                $rule->setErrorMessage('Invalid value');
            }

            return $test['testValue'];
        });

        $isValid = $rule->isValid($testData);

        static::assertEquals(false, $isValid);
        static::assertEquals('Invalid value', $rule->getErrorMessage());
    }

    public function testGetName() {
        $rule = new Rule('test name', function(array $test) {
            return $test['testValue'];
        });

        static::assertEquals('test name', $rule->getName());
    }

}
