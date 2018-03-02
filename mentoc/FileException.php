<?php
/**
 * @author William Merfalen <github@bnull.net>
 * @package mentoc 
 * @license wtfpl  [@see https://www.wtfpl.net]
 */
namespace mentoc;
class FileException extends \Exception{
	public function __construct($param = null){
		parent::__construct($param);
	}
}
