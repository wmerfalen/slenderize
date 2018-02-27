<?php

namespace mentoc;
class ViewParser {
	const HTML_TAG = 'htmltags';
	const ATTRIBUTE = 'html-attribute';
	const JS_TAG = 'javascript:';
	const CODE_TAG = '-';
	const INDENT = "\t";
	const EOL = "\n";
	protected $m_file = '';
	protected $m_line_text = '';
	protected $m_line_tokens = [];
	protected $m_line = 0;
	protected $m_depth = 0;
	protected $m_sym = null;
	protected $m_token_index = 0;
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
	public function parse($view_file_name){
		$this->m_file = file($view_file_name);
		$this->m_block();
	}
	protected function m_nextsym(){
		if(strlen($this->m_line_text) == 0 && count($this->m_file) > $this->m_line){
			do{
				$this->m_line_text = $this->m_file[$this->m_line++];
			}while(strlen($this->m_line_text) == 0 && count($this->m_file) > $this->m_line);
			$this->m_token_index = 0;
			$this->m_line_tokens = $this->m_tokenize($this->m_line_text);
			var_dump($this->m_line_tokens);
			$this->m_sym = $this->m_line_tokens['tokens'][$this->m_token_index++];
			return;
		}
		if(count($this->m_line_tokens) > ++$this->m_token_index){
			$this->m_sym = $this->m_line_tokens[$this->m_token_index];
			return;
		}
		$this->m_sym = null;
		return;
	}
	protected function m_store_until(&$store_here,$line,$start_at,$function){
		do{
			$store_here .= $line[$start_at++];
		}while($function($line[$start_at]) == false && strlen($line) > $start_at);
		return $start_at;
	}
	protected function m_tokenize($line_text){
		$current = '';
		$potential_attribute = '';
		$tokens = [];
		$is_attribute = false;
		$attribute_seen = false;
		$is_text_content = false;
		$text_content = null;
		$token_count = 0;
		for($i=0; $i < strlen($line_text);$i++){
			if(preg_match('|[^\'"= ]{1}|',$line_text[$i])){
				$current .= $line_text[$i];
				continue;
			}
			if($line_text[$i] == '='){
				if(strlen($current)){
					$is_attribute = true;
					$attribute_seen = true;
				}
				$current .= $line_text[$i];
				continue;
			}

			if($line_text[$i] == ' ' && strlen($current)){
				if(!$is_attribute && $token_count > 0){
					$is_text_content = true;
					$text_content .= $current . ' ';
				}else{
					$tokens[] = $current;
				}
				$current = '';
				$is_attribute = false;
				$token_count++;
				continue;
			}
			if($line_text[$i] == '"' && $is_attribute){
				$i = $this->m_store_until($current,$line_text,$i,function($current_char) {
					return $current_char == '"';
				});
				$current .= '"';
				continue;
			}
			if($line_text[$i] == '\'' && $is_attribute){
				$i = $this->m_store_until($current,$line_text,$i,function($current_char) {
					return $current_char == '\'';
				});
				$current .= '\'';
				continue;
			}
		}
		if($is_text_content){
			$text_content .= ' ' . $current;
		}else{
			$tokens[] = $current;
		}
		return [
			'tag' => $tokens[0],
			'tokens' => $tokens,
			'text_content' => $text_content
		];
	}
	protected function m_block(){
		$this->m_nextsym();
		if($this->m_accept(self::HTML_TAG)){
			if($this->m_accept(self::EOL)){
				$this->m_depth++;
				return;
			}
		}
	}
	protected function m_accept($symbol) : bool {
		if($symbol == self::HTML_TAG){
			
		}
		return false;
	}
	protected function m_expect($symbol) : bool {

	}
}
