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
 * newline = "\n";
 * not double quote = { letter | digit | symbol | single quote };
 * not single quote = { letter | digit | symbol | double quote };
 * content = { letter | digit | symbol | quote | space };
 * variable  = "{{$", letter, { letter | digit }, [ ";" ], "}}" 
 *  | 
 *  "{{$", letter, { letter | digit }, "->", letter, { letter | digit }, "()", [ ";" ], "}}";
 * attribute = { letter | digit } , [ dash | { letter | digit } ], equals, 
 *  single quote, { not single quote | variable }, single quote 
 *  |
 *  { letter | digit } , [ dash | { letter | digit } ], equals, double quote, 
 *  { not double quote | variable }, double quote;
 * tag = letter |  { letter | digit } | variable;
 * line = tag, [ space , { attribute | variable }, [ space ] ], newline
 *  |
 *  { tab }, tag, [ space, { attribute | variable }, [ space ] ], newline
 *  |
 *  tag, newline
 *  |
 *  { tab }, tag, newline;
 *  |
 *  tag, "|", { content }, newline
 *  |
 *  { tab }, tag, "|", { content }, newline
 */
namespace mentoc;
use \mentoc\View as View;
class ViewParser {
	protected const DIGIT = '|[0-9]{1}|';
	protected const SYMBOL = '|[\\x21\\x23-\\x26\\x28-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\x7e]{1}|';
	protected const LETTER = '|[a-zA-Z]{1}|';
	protected const SINGLE_QUOTE = '|\'{1}|';
	protected const DOUBLE_QUOTE = '|"{1}|';
	protected const INDENT = '|\\x09{1}|';
	protected const EOL = '|\\x0a{1}|';
	/** @var string $m_file The absolute or relative file path of the view. */
	protected $m_file = '';
	/** @var int $m_depth */
	protected $m_depth = 0;
	/** @var string $m_read_buffer read buffer */
	protected $m_read_buffer = null;
	/** @var int $m_read_chunk_size the size of the chunks we read from the file */
	protected $m_read_chunk_size = 250000;
	/** @var int $m_read_file_position the byte offset in the file that we are currently at */
	protected $m_read_file_position = 0;
	/** @var int $m_view_file_size file size in bytes of the view file */
	/** @var bool $m_eof true if we are at the end of the view file */
	protected $m_eof = false;
	protected $m_view_file_size = 0;
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
	public function __construct(array $options = []){
		
	}
	/**
	 * @param string $view_file_name The absolute or relative file path to the view to process
	 * @return \mentoc\View
	 */
	public function parse($view_file_name) : View {
		/** @todo if the file hasn't been modified, generate the view from the cached and previously generated php file */
		$this->m_file_pointer = fopen($view_file_name,'r');
		if($this->m_file_pointer === false){
				return new View(['error' => true,'loaded' => false,
						'reason' => 'Could not open view file']);
		}
		$this->m_view_file_size = filesize($view_file_name);
		return new View();
	}
	/**
	 * Reads another chunk of memory from the file pointer and stores it in 
	 * a member variable. Returns the number of bytes read. 
	 * @return int 
	 */
	protected function m_read_chunk() : int {
		$chars = fread($this->m_file_pointer,$this->m_read_chunk_size);
		if($char === false){ 
				$this->m_eof = true;
				return 0;
		}
		$this->m_read_buffer = $chars;
		return strlen($chars);
	}
	/**
	 * Tokenizes and stores the next symbols in our member variables.
	 * @return string|null 
	 */
	protected function m_nextsym(){
		if($this->m_read_file_position >= $this->m_view_file_size){
			$this->m_eof = true;
			return null;
		}
		if($this->m_read_chunk_size % $this->m_read_file_position == 0 && 
				$this->m_read_file_position > 0){
				if($this->m_read_chunk() == 0){
						return null;
				}
				$this->m_read_file_position = 0;
		}
		return $this->m_read_buffer[$this->m_read_file_position++];
	}
	protected function m_accept($regex) : bool {
		return false;
	}
	protected function m_expect($regex) : bool {
		
	}
}
