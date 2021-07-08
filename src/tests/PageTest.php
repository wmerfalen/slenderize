<?php

use PHPUnit\Framework\TestCase;

use slenderize\Page;
use slenderize\Config;

class PageTest extends TestCase
{
    public function testImmediateVarsOverrideConfigVars()
    {
        $view_dir = dirname(__FILE__) . '/foobar/views';
        $cache_dir = dirname(__FILE__) . '/foobar/cache';
        $page = new Page('empty', [ 'my_var' => '1234' ], compact('view_dir', 'cache_dir'));
        $this->assertEquals($view_dir, $page->get_view_dir());
        $this->assertEquals($cache_dir, $page->get_cache_dir());
    }
    public function testStaticConfigVariablesAreUsed()
    {
        $view_dir = dirname(__FILE__) . '/views';
        $cache_dir = dirname(__FILE__) . '/cache';
        $page = new Page('empty', [
            'my_var' => '1234'
        ], compact('view_dir', 'cache_dir'));
        $this->assertEquals($view_dir, $page->get_view_dir());
        $this->assertEquals($cache_dir, $page->get_cache_dir());
    }
    public function testPassingConfigurationVariablesToPages()
    {
        $view_dir = dirname(__FILE__) . '/views';
        $cache_dir = dirname(__FILE__) . '/cache';

        $page = new Page( 'empty', [ 'my_var' => '1234' ], compact('view_dir' ,'cache_dir'));
        $this->assertEquals($view_dir, $page->get_view_dir());
        $this->assertEquals($cache_dir, $page->get_cache_dir());
    }

    public function functionCallsAndOutputsProvider(): array
    {
        return [
            ['functioncall',[
                'my_object' => new class() {
                    public function foo()
                    {
                        return 'bar';
                    }
                }
                ]
            ],
            ['staticfunctioncall', []]
        ];
    }
    /**
     * @dataProvider functionCallsAndOutputsProvider
     */
    public function testCallingFunctionsOnObjectsThatArePassedToPages(string $function_name, array $optional_data = [])
    {
        $view_dir = dirname(__FILE__) . '/ViewParser/views';
        $cache_dir = dirname(__FILE__) . '/ViewParser/cache';
        $final_file = dirname(__FILE__) . "/ViewParser/views/{$function_name}-final";
        $page = new Page($function_name, $optional_data, compact('view_dir', 'cache_dir'));
        ob_start();
        $page->view();
        $output = ob_get_contents();
        ob_end_flush();
        $this->assertEquals($output, file_get_contents($final_file));
    }
    public function testStaticFunctionParsingWorksCorrectly()
    {
        $view_dir = dirname(__FILE__) . '/ViewParser/views';
        $cache_dir = dirname(__FILE__) . '/ViewParser/cache';
        $cache_file = "$cache_dir/staticfunctioncall.php";
        $expected = "$view_dir/staticfunctioncall-expected";
        $page = new Page('staticfunctioncall',[], compact('view_dir', 'cache_dir'));
        $page->view();
        $this->assertEquals(file_get_contents($expected), file_get_contents($cache_file));
    }
    public function testPassingVariablesToPages()
    {
        $title = 'Welcome to my page';
        $view_dir = dirname(__FILE__) . '/ViewParser/views';
        $cache_dir = dirname(__FILE__) . '/ViewParser/cache';
        $final_file = dirname(__FILE__) . '/ViewParser/views/title-final';
        $page = new Page('title', [
            'title' => $title
        ], compact('view_dir', 'cache_dir'));
        ob_start();
        $page->view();
        $output = ob_get_contents();
        ob_end_flush();
        $this->assertEquals($output, file_get_contents($final_file));
    }
}
