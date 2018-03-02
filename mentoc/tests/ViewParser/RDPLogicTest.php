<?php
use PHPUnit\Framework\TestCase;
use \mentoc\ViewParser as Parser;
class RDPLogicTest extends TestCase
{
	/**
	 * @dataProvider expectedAndActuals
	 */
	public function testExpectedAndActual($base_name){
		$rdp = new Parser();
		$this->assertEquals($rdp->parse(dirname(__FILE__) . '/views/' . $base_name),true);
		$this->assertEquals($rdp->compose(),file_get_contents(dirname(__FILE__) . '/views/' . $base_name . '-expected'));
	}

	public function expectedAndActuals() : array {
		return [
			['literal'],
			['multiple-lines'],
			['just-tags'],
		];
	}

	public function validViewsFromFileSystem(){
		$files = [
			'one-liner-body',
		];
		$file_source_array = [];
		foreach($files as $fname){
			$file_source_array[] = [dirname(__FILE__) . '/views/' . $fname];
		}
		return $file_source_array;
	}
}
