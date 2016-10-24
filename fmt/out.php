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
		echo "\n";
		self::$emptyline = true;
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

		echo "\n";
		self::$emptylines++;
		self::$emptyline = true;
	}

	static function str($s)
	{
		if(self::$emptyline) {
			echo str_repeat("\t", self::$indent);
			self::$emptyline = false;
			self::$emptylines = 0;
		}
		echo $s;
	}
}

?>
