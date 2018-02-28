<?php
/**
 * @author William Merfalen <github@bnull.net>
 * @package mentoc 
 * @license wtfpl  [@see https://www.wtfpl.net]
 */
namespace mentoc;
class View {
	/** @var bool $m_error if an error occured */
	protected $m_error = true;
	/** @var bool $m_loaded whether or not the view has been loaded */
	protected $m_loaded = false;
	/** @var array $m_options_user_can_set nuff said */
	protected $m_options_user_can_set = [
		'error','loaded'
	];
	/** @var array $m_available_getters key/value pairs of values that can be fetched using the __get magic method. They keys are the publicly visible names and the values are the internal variable names */
	protected $m_available_getters = [
		'error' => 'm_error',
		'loaded' => 'm_loaded'
	];
	public function __construct(array $options = []){
		if(is_array($options) && count($options)){
			$this->m_init_options($options);
		}
	}

	/**
	 * Overridable function to allow subsequent devs the opportunity to 
	 * customize which member variables can be written to upon object construction.
	 *
	 * @param array $options key/value pair of member variables that should be set
	 * @return void
	 */
	protected function m_init_options(array $options){
		foreach($options as $name => $value){
			if(isset($this->m_options_user_can_set[$name])){
				$this->{'m_' . $name} = $value;
			}
		}
	}
	public function __get($item){
		if(isset($this->m_available_getters[$item])){
			return $this->{ $this->m_available_getters[$item] };
		}
		/** This is implicit, but I like to be explicit */
	 	return null;
	}
}
