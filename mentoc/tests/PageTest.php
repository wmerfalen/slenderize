<?php
use PHPUnit\Framework\TestCase;

use \mentoc\Page;
use \mentoc\Config;
class PageTest extends TestCase
{
	public function testImmediateVarsOverrideConfigVars(){
		$view_dir = dirname(__FILE__) . '/foobar/views';
		$cache_dir = dirname(__FILE__) . '/foobar/cache';
		Config::set('view_dir',$view_dir);
		Config::set('cache_dir',$cache_dir);
		$page = new Page('empty',[
			'my_var' => '1234'
		],[
			'view_dir' => 'foobar-views',
			'cache_dir' => 'foobar-cache'
		]);
		$this->assertEquals('foobar-views',$page->get_config('view_dir'));
		$this->assertEquals('foobar-cache',$page->get_config('cache_dir'));
	}
	public function testStaticConfigVariablesAreUsed(){
		$view_dir = dirname(__FILE__) . '/views';
		$cache_dir = dirname(__FILE__) . '/cache';
		Config::set('view_dir',$view_dir);
		Config::set('cache_dir',$cache_dir);
		$page = new Page('empty',[
			'my_var' => '1234'
		]);
		$this->assertEquals($view_dir,$page->get_config('view_dir'));
		$this->assertEquals($cache_dir,$page->get_config('cache_dir'));
	}
	public function testPassingConfigurationVariablesToPages(){
		$view_dir = dirname(__FILE__) . '/views';
		$cache_dir = dirname(__FILE__) . '/cache';

		$page = new Page('empty',[
			'my_var' => '1234'
		],
		[
			'view_dir' => $view_dir,
			'cache_dir' => $cache_dir
		]);
		$this->assertEquals($view_dir,$page->get_config('view_dir'));
		$this->assertEquals($cache_dir,$page->get_config('cache_dir'));
	}

    public function testCallingFunctionsOnObjectsThatArePassedToPages()
    {
		$view_dir = dirname(__FILE__) . '/ViewParser/views';
		$cache_dir = dirname(__FILE__) . '/ViewParser/cache';
		$final_file = dirname(__FILE__) . '/ViewParser/views/functioncall-final';
		Config::set('view_dir',$view_dir);
		Config::set('cache_dir',$cache_dir);
		$page = new Page('functioncall',[
			'my_object' => new class {
				public function foo(){
					return 'bar';
				}
			}
		]);
		ob_start();
		$page->view();
		$output = ob_get_contents();
		ob_end_flush();
        $this->assertEquals($output,file_get_contents($final_file));
	}
    public function testPassingVariablesToPages()
    {
		$title = 'Welcome to my page';
		$view_dir = dirname(__FILE__) . '/ViewParser/views';
		$cache_dir = dirname(__FILE__) . '/ViewParser/cache';
		$final_file = dirname(__FILE__) . '/ViewParser/views/title-final';
		Config::set('view_dir',$view_dir);
		Config::set('cache_dir',$cache_dir);
		$page = new Page('title',[
			'title' => $title
		]);
		ob_start();
		$page->view();
		$output = ob_get_contents();
		ob_end_flush();
        $this->assertEquals($output,file_get_contents($final_file));
    }
}
