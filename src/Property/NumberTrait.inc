<?php
/**
 * NumberTrait for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Property;

/**
 * NumberTrait Trait.
 */
trait NumberTrait
{


    /**
     * Increments the number/integer value by the offset.
     *
     * @param mixed $offset The amount to increment by.
     * @return void.
     */
    final public function increment(int $value=1)
    {
        if ($value < 1) {
            throw new \Exception('Decrement value must be greater than 0');
        }

        $oldValue = $this->getValue();
        $newValue = ($oldValue + $value);
        $this->setValue($newValue);

    }//end increment()


    /**
     * Decrements the number/integer value by the offset.
     *
     * @param mixed $offset The amount to increment by.
     * @return mixed.
     */
    final public function decrement(int $value=1)
    {
        if ($value < 1) {
            throw new \Exception('Decrement value must be greater than 0');
        }

        $oldValue = $this->getValue();
        $newValue = ($oldValue - $value);
        $this->setValue($newValue);

    }//end decrement()


}//end trait