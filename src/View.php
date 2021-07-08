<?php
/**
 * @author William Merfalen <wmerfalen@gmail.com>
 * @package slenderize
 * @license wtfpl  [@see https://www.wtfpl.net]
 */

namespace slenderize;

class View
{
    /** @var bool $m_error if an error occured */
    protected $m_error = true;
    /** @var bool $m_loaded whether or not the view has been loaded */
    protected $m_loaded = false;
    protected $m_view_name = null;
    public function __construct($view_name = null, $options = null)
    {
        $this->m_view_name = $view_name;
        if (is_array($options) && count($options)) {
            $this->m_options = $options;
        }
    }
}
