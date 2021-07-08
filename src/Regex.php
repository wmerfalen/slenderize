<?php
/**
 * @author William Merfalen <wmerfalen@gmail.com>
 * @package slenderize
 * @license wtfpl  [@see https://www.wtfpl.net]
 */

namespace slenderize;

class Regex
{
    use Traits\Configurator;
    /** @var string $m_string The actual regex string */
    protected $m_string = null;
    protected $m_friendly_string = 'null';
    public function __construct(array $options = [])
    {
        if (is_array($options) && count($options)) {
            $this->m_init_options(
                $options,
                [
                    'get' => ['regex' => 'm_string','friendly' => 'm_friendly_string'],
                    'set' => ['regex' => 'm_string']
                ]
            );
            if (isset($options['friendly'])) {
                $this->m_friendly_string = $options['friendly'];
            }
        }
    }
    public function __toString()
    {
        return $this->m_friendly_string;
    }
}
