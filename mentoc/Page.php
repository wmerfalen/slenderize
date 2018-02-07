<?php

class Page {
	private $m_view_dir = 'views';
	protected $m_config_vars = [
		'view_dir'
	];
	public static function redirect($page){
		die(header('Location: /refresh/router.php?page=' . preg_replace('|[^a-z0-9]+|','',$page)));
	}
	public function __construct(array $config = []){
		foreach($config as $item => $value){
			$this->config($item,$value);
		}
	}
	public function config($item,$value) {
		if(!in_array($item,$this->m_config_vars)){
			throw 'Unknown configuration item specified';
		}
		$var_name = 'm_' . $item;
		$this->{$var_name} = $value;
	}
	public function view($name) {
		if(file_exists($file = $this->m_view_dir . '/' . preg_replace('|[^a-z0-9\-]+|','',$name))){

	}
}
