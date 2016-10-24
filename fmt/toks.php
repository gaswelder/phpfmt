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

		$vskip = 0;
		if($t[0] == T_WHITESPACE) {
			$vskip = substr_count($t[1], "\n") - 1;
			if($vskip < 0) $vskip = 0;
			$t = $this->pop();
		}
		$t['vskip'] = $vskip;
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
