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
		'letter' => '|[a-zA-Z]{1}|',
		'content' => '|[^\\x0a]{1}|'
	];
	/** @var \mentoc\FIFO $m_html FIFO structure of opening tags */
	protected $m_html = null;
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
	/** @var string $m_shared_read_buffer A read buffer that is shared across m_line calls */
	protected $m_shared_read_buffer = null;
	/** @var string $m_read_buffer read buffer */
	protected $m_read_buffer = null;
	const READ_CHUNK_SIZE = 250000;
	/** @var string $m_current_tag The currently parsed tag */
	protected $m_current_tag = '';
	/** @var int $m_read_file_position the byte offset in the file that we are currently at */
	protected $m_read_file_position = 0;
	/** @var bool $m_eof true if we are at the end of the view file */
	protected $m_eof = false;
	/** @var int $m_view_file_size file size in bytes of the view file */
	protected $m_view_file_size = 0;
	/** @var \mentoc\LIFO $m_tag_stack A LIFO struct of tags as they are opened */
	protected $m_tag_stack = null;
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
		$this->m_file_pointer = null;
		$this->m_increment_line_count = false;
		$this->m_error = null;
		$this->m_current_tag = '';
		$this->m_html = new FIFO();
		$this->m_tag_stack = new LIFO();
	}
	/**
	 * @param string $view_file_name The absolute or relative file path to the view to process
	 * @return bool
	 */
	public function parse($view_file_name) : bool {
		$this->m_init();
		if(!file_exists($view_file_name)){
			throw new FileException('Couldn\'t open file');
		}
		/** @todo if the file hasn't been modified, generate the view from the cached and previously generated php file */
		$this->m_file_pointer = fopen($view_file_name,'r');
		if($this->m_file_pointer === false){
			return false;
		}
		$this->m_view_file_size = filesize($view_file_name);
		return $this->m_program();
	}
	public function compose() : string {
		$html = '';
		do{ 
			$html .= $this->m_html->pop();
		}while(!$this->m_html->is_empty());
		return $html . PHP_EOL;
	}
	protected function m_program() : bool {
		while($this->m_line()){}
		return $this->m_error === null;
	}
	protected function m_register_tag($value){
		if(is_array($value) && isset($value['content'])){
			$this->m_html->push($value['content']);
			return;
		}else if(is_array($value) && isset($value['close'])){
			$this->m_html->push('</' . $value['close'] . '>');
		}else if(is_array($value) && isset($value['open'])){
			$this->m_html->push('<' . $value['open'] . '>');
		}else{
			$this->m_html->push('<' . $value . '>');
		}
	}
	protected function m_tag() : bool {
		$letters = 0;
		//@todo accept variable
		while($this->m_accept($this->regex('letter'))){ ++$letters; };
		return !!$letters;
	}
	protected function m_after_tag() : bool {
		if($this->m_accept("\n",false)){
			$this->m_register_tag(['open' => $this->m_shared_read_buffer]);
			$this->m_tag_stack->push($this->m_shared_read_buffer);
			$this->m_depth++;
			return true;
		}else if($this->m_accept('|',false)){
			$this->m_register_tag(['open' => $this->m_shared_read_buffer]);
			$this->m_tag_stack->push($this->m_shared_read_buffer);
			$this->m_shared_read_buffer = '';
			$this->m_expect($this->regex('content'));
			while($this->m_accept($this->regex('content'))){ ;; }
			$this->m_register_tag(['content' => $this->m_shared_read_buffer ]);
			$this->m_accept("\n");
			$this->m_shared_read_buffer = '';
			$this->m_depth++;
			return true;
		}
		return false;
	}
	protected function m_line() : bool {
		$tabs = $this->m_tab();
		if($tabs < $this->m_depth){
			for(; $this->m_depth > $tabs; --$this->m_depth){
				$this->m_register_tag(['close' => $this->m_tag_stack->pop()]);
			}
			$this->m_depth = $tabs;
		}else{
			$this->m_depth = $tabs;
		}
		if($this->m_accept("\n",false)){
			return true;
		}
		if($this->m_tag() && $this->m_after_tag()){
			$this->m_shared_read_buffer = '';
			return true;
		}

		if($this->m_eof){
			return false;
		}
		return false;
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
		$chars = fread($this->m_file_pointer,self::READ_CHUNK_SIZE);
		if($chars === false){ 
			$this->m_eof = true;
			return 0;
		}
		$this->m_read_buffer = $chars;
		return strlen($chars);
	}
	/**
	 * Expects $times number of tabs
	 * @return int
	 */
	protected function m_tab() : int { 
		$tab_count = 0;
		while($this->m_accept("\t",false)){
			++$tab_count;
		}
		return $tab_count;
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
		if(self::READ_CHUNK_SIZE == $this->m_read_file_position){
			if($this->m_read_chunk() == 0){
				return null;
			}
			$this->m_read_file_position = 0;
		}
		if(empty($this->m_read_buffer)){
			if($this->m_read_chunk() == 0){
				return null;
			}
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
	 * @param bool $store_next_char Whether or not the function should store the accepted char in $this->m_shared_read_buffer
	 * @return bool
	 */
	protected function m_accept($expected_string,$store_next_char = true) : bool {
		static $accepted = true;
		static $next_char;
		if($accepted){
			$next_char = $this->m_nextsym();
		}
		if($next_char === null){
			/** signifies EOF */
			return false;
		}
		if($expected_string instanceof Regex){
			$accepted = preg_match($expected_string->regex,$next_char);
			if($accepted && $store_next_char){
				$this->m_shared_read_buffer .= $next_char;
			}
			return $accepted;
		}else if(is_string($expected_string)){
			$accepted = $next_char === $expected_string;
			if($accepted && $store_next_char){
				$this->m_shared_read_buffer .= $next_char;
			}
			return $accepted;
		}else{
			throw 'Invalid type passed to m_accept: ' . gettype($expected_string);
		}
		$accepted = false;
		return false;
	}
	/**
	 * Expects a symbol. If that symbol is not present, m_error is called 
	 * with an error message.
	 * @param mixed $expected_string The string or Regex to expect
	 * @return bool
	 */
	protected function m_expect($expected_string,$save_char = true) : bool {
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
			if($save_char){
				$this->m_shared_read_buffer .= $next_char;
			}
			return true;
		}else if(is_string($expected_string)){
			if($next_char !== $expected_string){
				$this->m_error('Expected "' . $expected_string . '" on line: ' . $this->m_line . '. Instead, we found: "' . $next_char . '".');
				return false;
			}
			if($save_char){
				$this->m_shared_read_buffer .= $next_char;
			}
			return true;
		}else{
			throw new \Exception('Invalid type passed to m_expect: ' . gettype($expected_string));
		}
	}
}
