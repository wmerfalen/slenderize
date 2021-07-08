<?php

namespace slenderize;

use slenderize\Config;

class Page
{
    protected $m_view_name = null;
    protected $m_view_dir = 'views';
    protected $m_cache_dir = 'cache';
    /** @var array $m_variable_bindings Variable bindings */
    protected $m_variable_bindings = [];

    /** TODO: this needs to go. bad code smell */
    public static function redirect($page)
    {
        die(header('Location: /refresh/router.php?page=' . preg_replace('|[^a-z0-9]+|', '', $page)));
    }

    /**
     * Main entry point. Pass in the
     *
     *
     * @param string $view_name the relative path to the view
     * @param array $variable_bindings optional variable bindings to pass to the view
     * @param array $config optional configuration variables. 'view_dir' and 'cache_dir' are available
     */
    public function __construct(
        string $view_name,
        array $variable_bindings = [],
        array $config = []
    )
    {
        if (isset($config['view_dir'])) {
            $this->set_view_dir($config['view_dir']);
        }
        if (isset($config['cache_dir'])) {
            $this->set_cache_dir($config['cache_dir']);
        }
        $this->m_variable_bindings = $variable_bindings;
        $this->m_view_name = $view_name;
    }

    public function set_view_dir(string $view_dir)
    {
        $this->m_view_dir = $view_dir;
    }

    public function set_cache_dir(string $cache_dir)
    {
        $this->m_cache_dir = $cache_dir;
    }

		public function get_view_dir() : ?string
		{
			return $this->m_view_dir;
		}

		public function get_cache_dir() : ?string
		{
			return $this->m_cache_dir;
		}

    protected function m_serve(string $php_file)
    {
        $view_data = $this->m_variable_bindings;
        require $php_file;
    }

    protected function m_sanitize_view_name(string $name): string
    {
        return preg_replace('|[^a-zA-Z0-9_\.\\-]+|', '', $name);
    }

    public function view($name=null)
    {
        if ($name === null) {
            $name = $this->m_view_name;
        }
        $name = $this->m_sanitize_view_name($name);
        $view = new View();
        $cache_file = $this->m_cache_dir . '/' . $name . '.php';
        $view_file = $this->m_view_dir . '/' . $name;
        $parse = false;
        if (file_exists($cache_file)) {
            if (filemtime($cache_file) != filemtime($view_file)) {
                $parse = true;
            }
        } else {
            $parse = true;
        }
        if ($parse) {
            $view_parser = new ViewParser();
            if ($view_parser->parse($view_file)) {
                file_put_contents($cache_file, $view_parser->compose());
            }
        }
        $this->m_serve($cache_file);
    }
}
