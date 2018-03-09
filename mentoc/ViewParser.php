<?php
/**
 * @author William Merfalen <github@bnull.net>
 * @package mentoc 
 * @license wtfpl  [@see https://www.wtfpl.net]
 *
 * ViewParser is a recursive descent parser that processes view files that
 * have a very similar syntax to slimrb view files.
 *
 * Language grammar: To see the language grammar refer to the file EBNF is this directory. 
 */
namespace mentoc;

class ViewParser {
	const READ_CHUNK_SIZE = 250000;
	/** @var array $m_regex_patterns Key/value pairs of regular expressions used throughout this class */
	protected $m_regex_patterns = [
		'digit' => '|[0-9]{1}|',
		'symbol' => '|[\\x21\\x23-\\x26\\x28-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\x7e]{1}|',
		'letter' => '|[a-zA-Z]{1}|',
		'content' => '|[^\\x0a]{1}|',
		'identifier' => '|[a-zA-Z0-9_]{1}|',
		'attribute-ident' => '|[a-zA-Z0-9\-]+|'
	];
	/** @var \mentoc\FIFO $m_html FIFO structure of opening tags */
	protected $m_html = null;
	/** @var array $m_error Error messages go here */
	protected $m_error = [];
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
		$this->m_error = [];
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
			throw new FileException('View file doesn\'t exist');
		}
		/** @todo if the file hasn't been modified, generate the view from the cached and previously generated php file */
		$this->m_file_pointer = fopen($view_file_name,'r');
		if($this->m_file_pointer === false){
			throw new FileException('Couldn\'t open view file');
		}
		$this->m_view_file_size = filesize($view_file_name);
		$rdp_status = $this->m_program();
		return $rdp_status;
	}
	/**
	 * Combines and returns the html generated from a previous call to parse()
	 * @return string
	 */
	public function compose() : string {
		$html = '';
		do{ 
			$html .= $this->m_html->pop();
		}while(!$this->m_html->is_empty());
		return $html . PHP_EOL;
	}
	protected function m_program() : bool {
		while($this->m_line()){ ;; }
		return empty($this->m_error);
	}
	/**
	 * Registers an error message
	 * @param string $message The message to register
	 * @return void
	 */
	public function error($message){
		$this->m_error[] = $message;
	}
	protected function m_register_multi(array $array_of_array){
		foreach($array_of_array as $index => $inner_array){
			$this->m_register_tag($inner_array);
		}
	}
	protected function m_register_tag(array $value){
		if(isset($value['content'])){
			$this->m_html->push($value['content']);
		}else if(isset($value['attribute'])){
			$this->m_html->push(' ' . trim($value['attribute']));
		}else if(isset($value['variable'])){
			$this->m_html->push('<?=$view_data[\'' . $value['variable'] . '\'];?>');
		}else if(isset($value['close'])){
			$this->m_html->push('</' . $value['close'] . '>');
		}else if(isset($value['open'])){
			$this->m_html->push('<' . $value['open'] . '>');
		}else if(isset($value['half-open'])){
			$this->m_html->push('<' . $value['half-open']);
		}else if(isset($value['raw'])){
			$this->m_html->push($value['raw']);
		}
	}
	protected function m_tag() : bool {
		$letters = 0;
		while($this->m_accept($this->regex('letter'))){ ++$letters; };
		return !!$letters;
	}
	protected function m_after_tag() : bool {
		if($this->m_accept("\n",false)){
			$this->m_register_tag(['open' => $this->m_shared()]);
			$this->m_tag_stack->push($this->m_shared());
			$this->m_depth++;
			return true;
		}else if($this->m_accept('|',false)){
			$this->m_register_tag(['open' => $this->m_shared()]);
			$this->m_tag_stack->push($this->m_shared());
			$this->m_clear_shared();
			$this->m_expect($this->regex('content'));
			while($this->m_accept($this->regex('content'))){ ;; }
			$this->m_register_tag(['content' => $this->m_shared() ]);
			$this->m_accept("\n");
			$this->m_clear_shared();
			$this->m_depth++;
			return true;
		}else{
			$loops = 0;
			$this->m_register_tag(['half-open' => $this->m_shared()]);
			$this->m_tag_stack->push($this->m_shared());
			$this->m_clear_shared();
			$this->m_expect(' ',false);
			$variable_name = '';
			do{
				if($this->m_variable($variable_name)){
					$this->m_register_multi([
						['raw' => ' '],['variable' => $variable_name]
					]);
				}
				if($this->m_attribute()){
				}
			}
			while($this->m_accept(' ',false));
			$this->m_register_tag(['raw' => '>']);
			$this->m_accept("\n");
			$this->m_clear_shared();
			$this->m_depth++;
			return true;
		}
		return false;
	}

	protected function m_attribute(): bool {
		if($this->m_accept($this->regex('attribute-ident'))){
			while($this->m_accept($this->regex('attribute-ident'))){ ;; }
			$this->m_expect("=",true);
			if($this->m_accept('"',true)){
			}
		}
		return false;
	}

	/**
	 * Throws a FatalErrorException with $message as it's constructor argument.
	 * @param string $message
	 * @return false
	 * @throws FatalErrorException
	 */
	public function halt($message){
		throw FatalErrorException($message);
	}
	
	/**
	 * Reports a syntax error. A syntax error is a fatal and unrecoverable. 
	 * @param string $message
	 * @return void
	 */
	protected function m_syntax_error($message){
		$this->halt($message);
	}
	protected function m_clear_shared(){
		$this->m_shared_read_buffer = '';
	}
	protected function m_shared() : string {
		return $this->m_shared_read_buffer;
	}
	/**
	 * Parses a variable declaration, if one exists. Returns true if a variable was parsed. The name of the variable is stored in $variable_name. 
	 * @param string $variable_name Where to store the variable name. If no variable name is extracted, this function will set this parameter to null
	 * @return bool
	 */
	protected function m_variable(&$variable_name) : bool {
		if($this->m_accept('{',false)){
			if($this->m_expect('{',false)){
				if($this->m_expect('$',false) && 
					$this->m_identifier($variable_name) &&
					$this->m_expect('}',false) &&
					$this->m_expect('}',false)){
					return true;
					}
			}else{
				$this->m_syntax_error('Expected "{" after "{" on line: ' . $this->m_line);
			}
		}
		$variable_name = null;
		return false;
	}
	/**
	 * Parses an identifier as per our EBNF rules.
	 * @param string $identifier Where to store the identifier characters. 
	 * @param string $initial_mode Defaults to 'expect' in which case the first call will use m_expect. If set to any other value, this function will use m_accept as it's first call.
	 * @return bool
	 */
	protected function m_identifier(&$identifier,$initial_mode='expect') : bool {
		if($initial_mode == 'expect'){
			$first_call = 'm_expect';
		}else{
			$first_call = 'm_accept';
		}
		$this->m_clear_shared();
		if($this->$first_call($this->regex('letter'))){
			while($this->m_accept($this->regex('identifier'))){ ;; }
			$identifier = $this->m_shared();
			return true;
		}else{
			if($initial_mode == 'expect'){
				$this->m_syntax_error('Identifiers must start with a letter.');
				return false;
			}
			return false;
		}
	}
	protected function m_line() : bool {
		$tabs = $this->m_tab();
		if($tabs < $this->m_depth){
			$this->m_clear_shared();
			for(; $this->m_depth > $tabs; --$this->m_depth){
				$this->m_register_tag(['close' => $this->m_tag_stack->pop()]);
			}
		}
		$this->m_depth = $tabs;
		if($this->m_accept("\n",false)){
			return true;
		}
		if($this->m_variable($variable_name)){
			$this->m_register_tag(['variable' => $variable_name]);
			if(!$this->m_expect("\n",false)){
				$this->m_syntax_error('Expected NEWLINE on line: ' . $this->m_line);
				return false;
			}
			return true;
		}
		if($this->m_tag() && $this->m_after_tag()){
			$this->m_clear_shared();
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
	 * Accepts a symbol, just like m_expect, but unlike m_expect it will 
	 * not report an error if that symbol is not present. 
	 * @param mixed $expected_string The string or Regex to accept
	 * @param bool $store_next_char Whether or not the function should store the accepted char in $this->m_shared_read_buffer
	 * @return bool
	 */
	protected function m_accept($expected_string,$store_next_char = true) : bool {
		static $accepted = true;
		static $next_char = null;
		$next_char = $this->m_nextsym();
		if($next_char === null){
			/** signifies EOF */
			return false;
		}
		
		if($expected_string instanceof Regex){
			$accepted = preg_match($expected_string->regex,$next_char);
			if($accepted && $store_next_char){
				$this->m_shared_read_buffer .= $next_char;
			}
			if(!$accepted){
				$this->m_read_file_position--;
			}
			return $accepted;
		}else if(is_string($expected_string)){
			$accepted = $next_char === $expected_string;
			if($accepted && $store_next_char){
				$this->m_shared_read_buffer .= $next_char;
			}
			if(!$accepted){
				$this->m_read_file_position--;
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
			$this->m_syntax_error('End of file reached. Expected ' . $expected_string);
			return false;
		}
		if($expected_string instanceof Regex){
			if(!preg_match($expected_string->regex,$next_char)){
				$this->m_syntax_error('Expected ' . $expected_string->friendly . ' on line: ' . $this->m_line);
				return false;
			}
			if($save_char){
				$this->m_shared_read_buffer .= $next_char;
			}
			return true;
		}else if(is_string($expected_string)){
			if($next_char !== $expected_string){
				$this->m_syntax_error('Expected "' . $expected_string . '" on line: ' . $this->m_line . '. Instead, we found: "' . $next_char . '".');
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
