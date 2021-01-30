<?php

namespace Foris\Easy\Cache\Tests;

/**
 * Class TestCase
 *
 * @method expectException($class)
 * @method expectExceptionMessage($message)
 * @method setExpectedException($class, $message = "", $code = null)
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Assert a exception was thrown.
     *
     * @param        $class
     * @param string $message
     */
    protected function assertThrowException($class, $message = '')
    {
        if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException($class, $message);
            return ;
        } else {
            $this->expectException($class);
            $this->expectExceptionMessage($message);
            return ;
        }
    }
}
