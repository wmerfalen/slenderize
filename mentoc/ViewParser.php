<?php
/**
 * @author William Merfalen <github@bnull.net>
 * @package mentoc 
 * @license wtfpl  [@see https://www.wtfpl.net]
 *
 * ViewParser is a recursive descent parser that processes view files that
 * have a very similar syntax to slimrb view files.
 *
 * Language grammar: [in EBNF]
 * letter = "A" | "B" | "C" | "D" | "E" | "F" | "G"
 *      | "H" | "I" | "J" | "K" | "L" | "M" | "N"
 *      | "O" | "P" | "Q" | "R" | "S" | "T" | "U"
 *      | "V" | "W" | "X" | "Y" | "Z" | "a" | "b"
 *      | "c" | "d" | "e" | "f" | "g" | "h" | "i"
 *      | "j" | "k" | "l" | "m" | "n" | "o" | "p"
 *      | "q" | "r" | "s" | "t" | "u" | "v" | "w"
 *      | "x" | "y" | "z" ; 
 * digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;
 * symbol = ? \x21 \x23-\x26 \x28-\x2f \x3a-\x40 \x5b-\x60 \x7b-\x7e ?;
 * space = " ";
 * all chars = { single quote | double quote | letter | digit | symbol };
 * quote = "'" | '"';
 * single quote = "'";
 * double quote = '"';
 * not double quote = { letter | digit | symbol | single quote };
 * not single quote = { letter | digit | symbol | double quote };
 * attribute = { letter | digit } , [ dash | { letter | digit } ], equals, 
 *  single quote, { not single quote }, single quote 
 *  |
 *  { letter | digit } , [ dash | { letter | digit } ], equals, double quote, 
 *  { not double quote }, double quote;
 * tag = letter | { letter | digit };
 * 
 */
namespace mentoc;
use \mentoc\View as View;
class ViewParser {
	const DIGIT = '|[0-9]{1}|';
	const SYMBOL = '|[\\x21\\x23-\\x26\\x28-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\x7e]{1}|';
	const LETTER = '|[a-zA-Z]{1}|';
	const SINGLE_QUOTE = '|\'{1}|';
	const DOUBLE_QUOTE = '|"{1}|';
	const INDENT = '|\\x09{1}|';
	const EOL = '|\\x0a{1}|';
	/** @var string $m_file The absolute or relative file path of the view. */
	protected $m_file = '';
	/** @var int $m_depth */
	protected $m_depth = 0;
	/** @var array $m_html_tags All recognized html tags. */
	protected $m_html_tags = ['a','abbr','address','area','article',
		'aside','audio','b','base','bdi','bdo','blockquote','body',
		'br','button','canvas','caption','cite','code','col','colgroup',
		'data','datalist','dd','del','dfn','div','dl','dt','em','embed',
		'fieldset','figcaption','figure','footer','form','h1','h2','h3','h4','h5',
		'h6','head', 'header','hr','html','i','iframe','img','input','ins',
		'kbd','keygen', 'label','legend','li','link','main','map','mark',
		'meta','meter', 'nav','noscript','object','ol','optgroup','option',
		'output','p', 'param','pre','progress','q','rb','rp','rt','rtc','ruby','s',
		'samp','script','section','select','small','source','span',
		'strong','style','sub','sup','table','tbody','td','template',
		'textarea','tfoot','th','thead','time','title','tr','track',
		'u','ul','var','video','wbr'
	];
	/**
	 * @param string $view_file_name The absolute or relative file path to the view to process
	 * @return void
	 */
	public function parse($view_file_name) : View {
		$this->m_file_pointer = fopen($view_file_name,'r');
		if($this->m_file_pointer === false){
				return new View(['error' => true,'loaded' => false,
						'reason' => 'Could not open view file']);
		}
		return new View();
	}
	/**
	 * Tokenizes and stores the next symbols in our member variables.
	 * @return void
	 */
	protected function m_nextsym(){
		return;
	}
	protected function m_accept($symbol) : bool {
		return false;
	}
	protected function m_expect($symbol,$times = 1) : bool {

	}
}
