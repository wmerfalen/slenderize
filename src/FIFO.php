<?php
/**
 * @author William Merfalen <wmerfalen@gmail.com>
 * @package slenderize
 * @license wtfpl  [@see https://www.wtfpl.net]
 */

namespace slenderize;

class FIFO
{
    /** @var array $m_stack The stack */
    protected $m_stack = [];
    /**
     * Checks if the stack is empty
     * @return bool
     */
    public function is_empty(): bool
    {
        return empty($this->m_stack);
    }
    /**
     * Pushes a value onto the stack
     * @param mixed $item The value to push onto the stack
     * @return void
     */
    public function push($item)
    {
        array_push($this->m_stack, $item);
    }
    /**
     * Pops a value from the stack
     * @return mixed
     */
    public function pop()
    {
        return array_shift($this->m_stack);
    }
}
