<?php
/**
 * @author William Merfalen <wmerfalen@gmail.com>
 * @package slenderize
 * @license wtfpl  [@see https://www.wtfpl.net]
 */

namespace slenderize\Exceptions;

class InvalidTypeAcceptedException extends \Exception
{
    public function __construct($param = null)
    {
        parent::__construct($param);
    }
}
