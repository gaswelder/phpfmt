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

	static function lf()
	{
		/*
		 * If we have already an empty line above, don't add another
		 * one.
		 */
		if(self::$emptylines > 0) return;

		/*
		 * If current line is empty, it becomes an empty line above.
		 */
		if(self::$emptyline) {
			self::$emptylines++;
		}

		echo "\n";
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
