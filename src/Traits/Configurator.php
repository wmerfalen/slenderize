<?php
/**
 * @author William Merfalen <wmerfalen@gmail.com>
 * @package slenderize
 * @license wtfpl  [@see https://www.wtfpl.net]
 */

namespace slenderize\Traits;

trait Configurator
{
    /** @var array $m_configurator_options_user_can_set nuff said */
    protected $m_configurator_options_user_can_set = [
        /* example:
         * 'error' => 'm_error',
         * 'loaded' => 'm_loaded'
         */
    ];
    /** @var array $m_configurator_options_user_can_get key/value pairs of values that can be fetched using the get_option method. They keys are the publicly visible names and the values are the internal variable names */
    protected $m_configurator_options_user_can_get = [
        /* example:
         * 'error' => 'm_error',
         * 'loaded' => 'm_loaded'
         */
    ];
    /**
     * Overridable function to allow subsequent devs the opportunity to
     * customize which member variables can be written to upon object construction.
     *
     * @param array $options key/value pair of member variables that should be set
     * @return void
     */
    protected function m_init_options(array $options, array $mappings)
    {
        if (isset($mappings['set'])) {
            $this->m_configurator_options_user_can_set = $mappings['set'];
        }
        if (isset($mappings['get'])) {
            $this->m_configurator_options_user_can_get = $mappings['get'];
        }
        if (isset($mappings['both'])) {
            $this->m_configurator_options_user_can_get =
                $this->m_configurator_options_user_can_set = $mappings['both'];
        }
        foreach ($options as $name => $value) {
            $this->m_set_option($name, $value);
        }
    }
    /**
     * The workhorse for the getting of variables
     * @param string $item The value to get
     * @return mixed
     */
    protected function m_get_option($item)
    {
        if (isset($this->m_configurator_options_user_can_get[$item])) {
            return $this->{ $this->m_configurator_options_user_can_get[$item] };
        }
        /** This is implicit, but I like to be explicit */
        return null;
    }
    /**
     * The workhorse for setting variables
     * @param string $item The item to set
     * @param mixed $value the value to set the item to
     * @return void
     */
    protected function m_set_option($item, $value)
    {
        if (isset($this->m_configurator_options_user_can_set[$item])) {
            $this->{ $this->m_configurator_options_user_can_set[$item] } = $value;
        }
    }
    public function __set($item, $value)
    {
        return $this->m_set_option($item, $value);
    }
    public function __get($item)
    {
        return $this->m_get_option($item);
    }
}
