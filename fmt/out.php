<?php

class out
{
	static $indent = 0;
	/*
	 * Whether current line is still empty
	 */
	private static $emptyline = true;
	/*
	 * Number of empty lines above the current line
	 */
	private static $emptylines = 0;

	private static $linelen = 0;

	private static $out = "";

	static function flush() {
		$s = self::$out;
		self::$out = "";
		self::$indent = 0;
		self::$emptyline = true;
		self::$emptylines = 0;
		self::$linelen = 0;
		return $s;
	}

	static function emptyline() {
		return self::$emptyline;
	}

	/*
	 * Finish current line, if it's not empty.
	 */
	static function nl()
	{
		if(self::$emptyline) {
			return;
		}
		self::$out .= "\n";
		self::$emptyline = true;
		self::$linelen = 0;
	}

	/*
	 * Separate previous output by an empty line.
	 * Doesn't do anything if there is an empty line already.
	 */
	static function vskip()
	{
		/*
		 * If we have already an empty line above, don't add another
		 * one.
		 */
		if(self::$emptylines > 0) return;

		self::$out .= "\n";
		self::$emptylines++;
		self::$emptyline = true;
		self::$linelen = 0;
	}

	static function linelen() {
		return self::$linelen + self::$indent * 4;
	}

	static function str($s)
	{
		if(self::$emptyline) {
			if(self::$indent > 0) {
				self::$out .= str_repeat("\t", self::$indent);
			}
			self::$emptyline = false;
			self::$emptylines = 0;
		}
		self::$linelen += mb_strlen($s);
		self::$out .= $s;
	}
}

?>
