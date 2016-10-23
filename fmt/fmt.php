<?php

class fmt
{
	static function format($src)
	{
		$s = new toks($src);
		ob_start();
		self::subformat($s, null, null);
		return ob_get_clean();
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
					self::ffunc($s);
					break;
				case T_CLASS:
					self::fclass($s);
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

	private static function out($tok, toks $s)
	{
		$ops = array(
			'=', '==', '===', '!=', '!==',
			'<', '>', '<=', '>=',
			'+', '-', '*', '/', '%',
			'+=', '-=', '*=', '%=', '.=',
			'=>',
			'||', '&&'
		);

		if(in_array($tok[1], $ops)) {
			out::str(' '.$tok[1].' ');
			return;
		}

		switch($tok[0])
		{
			case '}':
				out::$indent--;
				out::str('}');
				out::lf();
				return;
			case T_AS:
			case T_INSTANCEOF:
				out::str(' ');
				break;
			case T_COMMENT:
				$tok[1] = trim($tok[1]);
				break;
		}

		if(out::emptyline()) {
			if(substr_count($tok['lspace'], "\n") > 1) {
				out::lf();
			}
		}

		out::str($tok[1]);

		switch($tok[0])
		{
			case T_COMMENT:
				out::lf();
				break;
			case ';':
				out::lf();
				break;
			case '{':
				out::lf();
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
			T_AS,
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
			T_INSTANCEOF,
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
		out::lf();
		out::$indent++;

		$n = count($contents);
		while($n > 1) {
			$t = array_shift($contents);
			$n--;
			if($t[0] == ',') {
				out::str(',');
				out::lf();
			}
			else {
				self::out($t, $s);
			}
		}

		assert($contents[0][0] == ')');
		out::lf();
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

	private static function fclass(toks $s)
	{
		out::str('class ');
		self::out_until($s, '{');
		out::lf();
	}

	private static function fcontrol($t, toks $s)
	{
		out::str($t[1].' ');
		/*
		 * Add a space after the condition
		 */
		$a = self::read_contents($s, '(', ')');
		foreach($a as $t) {
			self::out($t, $s);
		}
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

	private static function ffunc(toks $s)
	{
		if(out::emptyline()) {
			out::lf();
		}
		out::str('function ');
		/*
		 * Put opening brace on new line
		 */
		self::out_until($s, '{');
		out::lf();

		/*
		 * Add newline after the body
		 */
		self::subformat($s, '{', '}');
		out::lf();
	}

	private static function fswitch(toks $s)
	{
		out::str('switch ');
		self::subformat($s, '(', ')');
		out::str(' ');

		$a = self::read_contents($s, '{', '}');
		self::out(array_shift($a), $s);

		$case = false;
		foreach($a as $t)
		{
			if($t[0] == T_CASE) {
				$case = true;
				out::$indent--;
				out::str('case ');
				continue;
			}
			if($case && $t[0] == ':') {
				$case = false;
				out::str(':');
				out::lf();
				out::$indent++;
				continue;
			}

			self::out($t, $s);
		}
	}
}

?>
