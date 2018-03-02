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
 * literal = "|";
 * single quote = "'";
 * double quote = '"';
 * newline = "\n";
 * escaped quote = "\'" | '\"';
 * not double quote = { letter | digit | symbol | single quote };
 * not single quote = { letter | digit | symbol | double quote };
 * variable  = "{{$", identifier, [ ";" ], "}}";
 * function call =  "{{$", identifier, "->", identifier, "()", [ ";" ], "}}";
 * content = { letter | digit | symbol | quote | space | variable | function call };
 * identifier = letter, { letter | digit | "_" };
 * quoted string = single quote, { escaped quote | letter | digit | symbol | space }, single quote
 *  |
 *  double quote, { escaped quote | letter | digit | symbol | space }, double quote
 *  |
 * attribute = { letter | digit } , [ dash | { letter | digit } ], equals, 
 *  single quote, { not single quote | variable }, single quote 
 *  |
 *  { letter | digit } , [ dash | { letter | digit } ], equals, double quote, 
 *  { not double quote | variable }, double quote;
 * literal content = literal, content, [ { content } ];
 * tag = letter |  { letter | digit } | variable;
 * after tag = space, { attribute | variable | function call }, [ space ] 
 *  |
 *  space, { attribute | variable | function call }, space, literal content;
 * line = tag, after tag, newline
 *  |
 *  { tab }, tag, after tag, newline
 *  |
 *  tag, newline
 *  |
 *  { tab }, tag, newline;
 *  |
 *  tag, literal content, newline
 *  |
 *  { tab }, tag, literal content, newline
 */
namespace mentoc;

class ViewParser {
	/** @var array $m_regex_patterns Key/value pairs of regular expressions used throughout this class */
	protected $m_regex_patterns = [
		'digit' => '|[0-9]{1}|',
		'symbol' => '|[\\x21\\x23-\\x26\\x28-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\x7e]{1}|',
		'letter' => '|[a-zA-Z]{1}|'
	];
	/** @var string $m_error Error messages go here */
	protected $m_error = null;
	/** @var int $m_line The current line number being processed */
	protected $m_line = 1;
	/** @var bool $m_increment_line_count Whether or not to increment the $m_line variable next time m_nextsym is called */
	protected $m_increment_line_count = false;
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
	/** @var bool $m_eof true if we are at the end of the view file */
	protected $m_eof = false;
	/** @var int $m_view_file_size file size in bytes of the view file */
	protected $m_view_file_size = 0;
	public function __construct(array $options = []){
		$this->m_init();	
	}
	protected function m_init(){
		$this->m_line = 1;
		$this->m_depth = 0;
		$this->m_read_buffer = null;
		$this->m_read_file_position = 0;
		$this->m_eof = false;
		$this->m_view_file_size = 0;
		$this->m_file_pointer = fopen($view_file_name,'r');
		$this->m_increment_line_count = false;
		$this->m_error = null;
	}
	/**
	 * @param string $view_file_name The absolute or relative file path to the view to process
	 * @return \mentoc\View
	 */
	public function parse($view_file_name) : View {
		$this->m_init();
		/** @todo if the file hasn't been modified, generate the view from the cached and previously generated php file */
		if($this->m_file_pointer === false){
			return new View(['error' => true,'loaded' => false, 'reason' => 'Could not open view file']);
		}
		$this->m_view_file_size = filesize($view_file_name);
		$view = new View();
	}
	/**
	 * Returns a new Regex object constructed from the $m_regex_patterns variable
	 * @param string $key The type of regex to create
	 * @return Regex
	 */
	public function regex($key) : Regex {
		if(isset($this->m_regex_patterns[$key])){
			return new Regex(
				[
					'regex' => $this->m_regex_patterns[$key],
					'friendly' => $key
				]
			);
		}else{
			return new Regex();
		}
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
	 * Expects $times number of tabs
	 * @param int $times The number of tabs to expect
	 * @return bool
	 */
	protected function m_tab($times = 1) : bool { 
		if($times < 0){
			return false;
		}
		for($i = 0; $i < $times;$i++){
			if(!$this->m_expect("\t")){
				return false;
			}
		}
		return true;
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
		if($this->m_increment_line_count){
			$this->m_line++;
			$this->m_increment_line_count = false;
		}
		$next_char = $this->m_read_buffer[$this->m_read_file_position];
		if($next_char == "\n"){
			$this->m_increment_line_count = true;
		}
		$this->m_read_file_position++;
		return $next_char;
	}
	/**
	 * Sets the error string
	 * @param string $message The error message to store
	 * @return void
	 */
	protected function m_error($message){
		$this->m_error = $message;
	}
	/**
	 * Accepts a symbol, just like m_expect, but unlike m_expect it will 
	 * not report an error if that symbol is not present. 
	 * @param mixed $expected_string The string or Regex to accept
	 * @return bool
	 */
	protected function m_accept($expected_string) : bool {
		$next_char = $this->m_nextsym();
		if($next_char === null){
			/** signifies EOF */
			return false;
		}
		if($expected_string instanceof Regex){
			return preg_match($expected_string->regex,$next_char);
		}else if(is_string($expected_string)){
			return $next_char === $expected_string;
		}else{
			throw 'Invalid type passed to m_accept: ' . gettype($expected_string);
		}
		return false;
	}
	/**
	 * Expects a symbol. If that symbol is not present, m_error is called 
	 * with an error message.
	 * @param mixed $expected_string The string or Regex to expect
	 * @return bool
	 */
	protected function m_expect($expected_string) : bool {
		$next_char = $this->m_nextsym();
		if($next_char === null){
			$this->m_error('End of file reached. Expected ' . $expected_string);
			return false;
		}
		if($expected_string instanceof Regex){
			if(!preg_match($expected_string->regex,$next_char)){
				$this->m_error('Expected ' . $expected_string->friendly . ' on line: ' . $this->m_line);
				return false;
			}
			return true;
		}else if(is_string($expected_string)){
			if($next_char !== $expected_string){
				$this->m_error('Expected "' . $expected_string . '" on line: ' . $this->m_line . '. Instead, we found: "' . $next_char . '".');
				return false;
			}
			return true;
		}else{
			throw 'Invalid type passed to m_expect: ' . gettype($expected_string);
		}
	}
}
