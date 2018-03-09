<?php

namespace mentoc;
use \mentoc\Config;
class Page {
	protected $m_view_name = null;
	protected $m_view_dir = 'views';
	protected $m_cache_dir = 'cache';
	protected $m_config_vars = [
		'view_dir','cache_dir'
	];
	/** @var array $m_variable_bindings Variable bindings */
	protected $m_variable_bindings = [];
	public static function redirect($page){
		die(header('Location: /refresh/router.php?page=' . preg_replace('|[^a-z0-9]+|','',$page)));
	}
	public function __construct(string $view_name,
			array $variable_bindings = [],
			array $config = []){
		$this->config('view_dir',Config::get('view_dir'));
		$this->config('cache_dir',Config::get('cache_dir'));
		foreach($config as $item => $value){
			$this->config($item,$value);
		}
		$this->m_variable_bindings = $variable_bindings;
		$this->m_view_name = $view_name;
	}
	public function config($item,$value) {
		if(!in_array($item,$this->m_config_vars)){
			throw new \Exception('Unknown configuration item specified');
		}
		$var_name = 'm_' . $item;
		$this->{$var_name} = $value;
	}
	/**
	 * Returns a configuration parameter if one exists
	 * @param $item
	 * @return mixed
	 */
	public function get_config(string $item){
		if(!in_array($item,$this->m_config_vars)){
			return null;
		}
		$var_name = 'm_' . $item;
		return $this->{$var_name};
	}
	protected function m_serve(string $php_file){
		$view_data = $this->m_variable_bindings;
		require $php_file;
	}
	protected function m_sanitize_view_name(string $name) : string {
		return preg_replace('|[^a-z0-9\-]+|','',$name);
	}
	public function view($name=null) {
		if($name === null){
			$name = $this->m_view_name;
		}
		$name = $this->m_sanitize_view_name($name);
		$view = new View();
		$cache_file = $this->get_config('cache_dir') . '/' . $name . '.php';
		$view_file = $this->get_config('view_dir') . '/' . $name;
		$parse = false;
		if(file_exists($cache_file)){
			if(filemtime($cache_file) != filemtime($view_file)){
				$parse = true;
			}
		}else{
			$parse = true;
		}
		if($parse){
			$view_parser = new ViewParser();
			if($view_parser->parse($view_file)){
				file_put_contents($cache_file,$view_parser->compose());
			}
		}
		$this->m_serve($cache_file);
	}
}
