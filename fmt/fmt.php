<?php

class fmt
{
	static function format($src)
	{
		$s = new toks($src);
		self::subformat($s, null, null);
		return out::flush();
	}

	static function subformat(toks $s, $begin, $end)
	{
		$t = $s->peek();
		if($begin) {
			if(!$t || $t[0] != $begin) return;
		}

		$t = $s->get();
		self::out($t, $s);

		$level = 1;
		while($t = $s->get())
		{
			switch($t[0])
			{
				case T_OPEN_TAG:
					$last_char = substr($t[1], -1);
					out::str(trim($t[1]));
					if($last_char == "\n") {
						out::nl();
					}
					else {
						out::str(' ');
					}
					break;
				case T_IF:
				case T_FOREACH:
				case T_WHILE:
					self::fcontrol($t, $s);
					break;
				case T_FOR:
					self::ffor($s);
					break;
				case T_SWITCH:
					self::fswitch($s);
					break;
				case T_ARRAY:
					self::farr($s);
					break;
				case T_FUNCTION:
				case T_CLASS:
					self::fobj($t, $s);
					break;
				case T_OPEN_TAG_WITH_ECHO:
					self::out($t, $s);
					out::str(' ');
					self::out_until($s, T_CLOSE_TAG);
					out::str(' ');
					break;
				default:
					self::out($t, $s);
			}

			if(!$begin) continue;

			if($t[0] == $begin) {
				$level++;
				continue;
			}
			if($t[0] == $end) {
				$level--;
				if($level == 0) {
					break;
				}
			}
		}
	}

	const LINELEN = 70;

	private static function out($tok, toks $s)
	{
		/*
		 * Line-breakers
		 */
		$breaks = array('.');
		if(out::linelen() > self::LINELEN && in_array($tok[1], $breaks)) {
			out::nl();
			out::$indent++;
			out::str($tok[1]);
			out::$indent--;
			return;
		}
		$breaks = array(',');
		if(out::linelen() > self::LINELEN && in_array($tok[1], $breaks)) {
			out::str($tok[1]);
			out::nl();
			out::str("\t");
			return;
		}

		$ops = array( '-', '+', '/', '*' );
		$p = $s->peek();
		if(in_array($tok[0], $ops) && $p && $p[0] == T_LNUMBER) {
			out::str($tok[0]);
			return;
		}

		/*
		 * Separate by space from both sides
		 */
		$lrspace = array(
			T_AND_EQUAL,
			T_AS,
			T_BOOLEAN_AND,
			T_BOOLEAN_OR,
			T_CONCAT_EQUAL,
			T_DIV_EQUAL,
			T_DOUBLE_ARROW,
			T_INSTANCEOF,
			T_IS_EQUAL,
			T_IS_GREATER_OR_EQUAL,
			T_IS_IDENTICAL,
			T_IS_NOT_EQUAL,
			T_IS_NOT_IDENTICAL,
			T_IS_SMALLER_OR_EQUAL,
			T_MINUS_EQUAL,
			T_MOD_EQUAL,
			T_MUL_EQUAL,
			T_OR_EQUAL,
			T_PLUS_EQUAL,
			T_SL,
			T_SL_EQUAL,
			T_SR,
			T_SR_EQUAL,
			T_XOR_EQUAL,
			'=', '<', '>', '+', '-', '*', '/', '%', '?', ':'
		);
		if(in_array($tok[0], $lrspace)) {
			out::str(' '.$tok[1].' ');
			return;
		}

		switch($tok[0])
		{
			case '}':
				out::$indent--;
				out::str('}');
				out::nl();
				return;
			case T_COMMENT:
				$tok[1] = trim($tok[1]);
				break;
		}

		if(out::emptyline() && $tok['vskip'] > 0) {
			out::vskip();
		}
		out::str($tok[1]);

		switch($tok[0])
		{
			case T_COMMENT:
				out::nl();
				break;
			case ';':
				out::nl();
				break;
			case '{':
				out::nl();
				out::$indent++;
				break;
			case T_RETURN:
			case T_BREAK:
				$t = $s->peek();
				if($t && $t[0] != ';') {
					out::str(' ');
				}
				break;
		}

		$space_after = array(
			T_ABSTRACT,
			T_CATCH,
			T_CLASS,
			T_CLONE,
			T_CONST,
			T_DO,
			T_ECHO,
			T_ELSE,
			T_ELSEIF,
			T_EXTENDS,
			T_FINAL,
			T_FINALLY,
			T_FOR,
			T_FOREACH,
			T_FUNCTION,
			T_GLOBAL,
			T_GOTO,
			T_IF,
			T_IMPLEMENTS,
			T_INCLUDE,
			T_INCLUDE_ONCE,
			T_INSTEADOF,
			T_INTERFACE,
			T_NAMESPACE,
			T_NEW,
			T_PRIVATE,
			T_PUBLIC,
			T_PROTECTED,
			T_REQUIRE,
			T_REQUIRE_ONCE,
			T_STATIC,
			T_SWITCH,
			T_THROW,
			T_TRAIT,
			T_TRY,
			T_USE,
			T_VAR,
			T_WHILE,
			T_YIELD,
			','
		);

		if(in_array($tok[0], $space_after)) {
			out::str(' ');
		}
	}

	/*
	 * Reads sequence of tokens starting from token 'begin' and
	 * ending with token 'end', recursively including nested sequences.
	 */
	private static function read_contents(toks $s, $begin, $end)
	{
		$contents = array();
		$t = $s->get();
		assert($t[0] == $begin);
		$contents[] = $t;
		$level = 1;

		while($t = $s->get())
		{
			$contents[] = $t;
			if($t[0] == $begin) {
				$level++;
				continue;
			}
			if($t[0] == $end) {
				$level--;
				if($level == 0) {
					break;
				}
			}
		}

		return $contents;
	}

	private static function farr(toks $s)
	{
		out::str('array');

		$contents = self::read_contents($s, '(', ')');

		$len = 0;
		foreach($contents as $t) {
			$len += mb_strlen($t[1]);
		}

		if($len < 50) {
			foreach($contents as $t) {
				self::out($t, $s);
			}
			return;
		}

		// print the opening brace and go to the next line
		$t = array_shift($contents);
		assert($t[0] == '(');
		out::str('(');
		out::nl();
		out::$indent++;

		$n = count($contents);
		$level = 0;
		while($n > 1) {
			$t = array_shift($contents);
			$n--;
			/*
			 * Keep track of braces so we don't break on
			 * wrong commas.
			 */
			if($t[0] == '(') {
				$level++;
			}
			else if($t[0] == ')') {
				$level--;
			}

			if($level == 0 && $t[0] == ',') {
				out::str(',');
				out::nl();
			}
			else {
				self::out($t, $s);
			}
		}

		assert($contents[0][0] == ')');
		out::nl();
		out::$indent--;
		out::str(')');
	}

	private static function out_until(toks $s, $tokid)
	{
		while($t = $s->get()) {
			if($t[0] == $tokid) {
				$s->unget($t);
				break;
			}
			self::out($t, $s);
		}
	}

	private static function fcontrol($t, toks $s)
	{
		self::out($t, $s);
		/*
		 * Add a space after the condition
		 */
		self::subformat($s, '(', ')');
		out::str(' ');
	}

	private static function ffor(toks $s)
	{
		out::str('for ');

		$a = self::read_contents($s, '(', ')');
		foreach($a as $t) {
			switch($t[0]) {
				case ';':
					out::str('; ');
					break;
				default:
					self::out($t, $s);
			}
		}
		out::str(' ');
	}

	/*
	 * Formats function or class
	 */
	private static function fobj($t, toks $s)
	{
		self::out($t, $s);
		/*
		 * Put opening brace on new line
		 */
		self::out_until($s, '{');
		out::nl();

		/*
		 * Add newline after the body unless another '}' follows
		 */
		self::subformat($s, '{', '}');
		$t = $s->peek();
		if(!$t || $t[0] != '}') {
			out::vskip();
		}
	}

	private static function fswitch(toks $s)
	{
		out::str('switch ');
		self::subformat($s, '(', ')');
		out::str(' ');

		$t = $s->get();
		if(!$t || $t[0] != '{') {
			trigger_error("'{' expected");
			return;
		}
		self::out($t, $s);

		$level = 1;
		$case = false;
		while($t = $s->get())
		{
			if($t[0] == '}') {
				$level--;
				if($level == 0) {
					self::out($t, $s);
					break;
				}
			}
			else if($t[0] == '{') {
				$level++;
			}

			if($t[0] == T_CASE) {
				$case = true;
				out::$indent--;
				out::str('case ');
				continue;
			}
			if($t[0] == T_DEFAULT) {
				$case = true;
				out::$indent--;
				out::str('default');
				continue;
			}
			if($case && $t[0] == ':') {
				$case = false;
				out::str(':');
				out::nl();
				out::$indent++;
				continue;
			}

			self::out($t, $s);
		}
	}
}

?>
