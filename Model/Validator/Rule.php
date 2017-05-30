<?php

namespace CheckoutCom\Magento2\Model\Validator;

use Closure;

class Rule {

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Closure
     */
    protected $condition;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var string
     */
    protected static $defaultErrorMessagePattern = 'The rule [%s] does not match the given condition.';

    /**
     * Rule constructor.
     * @param $name
     * @param Closure $condition
     * @param string $errorMessage (optional)
     */
    public function __construct($name, Closure $condition, $errorMessage = '') {
        $this->name         = (string) $name;
        $this->condition    = $condition;
        $this->errorMessage = (string) $errorMessage;
    }

    /**
     * Returns the rule name (usually for internal use).
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage) {
        $this->errorMessage = (string) $errorMessage;
    }

    /**
     * Returns error message. If the error message has not been defined in the object constructor the default message will be returned.
     *
     * @return string
     */
    public function getErrorMessage() {
        if($this->errorMessage) {
            return $this->errorMessage;
        }

        return sprintf(self::$defaultErrorMessagePattern, $this->getName());
    }

    /**
     * Checks if the given rule condition is valid.
     *
     * @param array $data
     * @return bool
     */
    public function isValid(array $data) {
        return (bool) call_user_func($this->condition, $data, $this);
    }

    /**
     * Checks if the given rule condition is faulty.
     *
     * @param array $data
     * @return bool
     */
    public function isFailed(array $data) {
        return ! $this->isValid($data);
    }

}
