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

		$space = "";
		while(!empty($this->s)) {
			$p = $this->s[0];
			if(!$p || !is_array($p) || $p[0] != T_WHITESPACE) {
				break;
			}
			$space .= $p[1];
			$this->gettok();
		}

		$t = $this->gettok();
		if(!$t) return null;

		$t['lspace'] = $space;
		return $t;
	}

	private function gettok()
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
