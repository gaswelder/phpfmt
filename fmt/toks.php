<?php

class toks
{
	private $s;
	private $buf = array();

	function __construct($src) {
		$this->s = token_get_all($src);
	}

	function unget($t) {
		$this->buf[] = $t;
	}

	function peek() {
		$t = $this->get();
		if(!$t) return $t;
		$this->unget($t);
		return $t;
	}

	function get()
	{
		if(!empty($this->buf)) {
			return array_pop($this->buf);
		}

		$t = $this->pop();
		if(!$t) return null;

		/*
		 * If this is whitespace, note how many line breaks
		 * it has and get the next token.
		 */
		$lbreaks = 0;
		if($t[0] == T_WHITESPACE) {
			$lbreaks = substr_count($t[1], "\n");
			$t = $this->pop();
		}

		/*
		 * Mark the number of line breaks preceeded this token.
		 */
		$t['lbreaks'] = $lbreaks;
		return $t;
	}

	private function pop()
	{
		$tok = array_shift($this->s);
		if(!$tok) {
			return null;
		}

		if(!is_array($tok)) {
			$tok = array($tok, $tok);
		}
		return $tok;
	}
}

?>
