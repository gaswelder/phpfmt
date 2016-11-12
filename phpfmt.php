<?php
require "fmt/fmt.php";
require "fmt/out.php";
require "fmt/toks.php";

error_reporting(-1);

set_error_handler(function ($errno, $errstr, $file, $line) {
	fwrite(STDERR, "$errstr at $file:$line\n");
	exit(1);
});

main($argv);

function usage()
{
	fwrite(STDERR, "Usage: phpfmt [-rq] files...\n");
	fwrite(STDERR, "	-r	recurse into directories\n");
	fwrite(STDERR, "	-q	don't print names of changed files\n");
}

function main($args)
{
	$recursive = false;
	$print_names = true;

	array_shift($args);
	while (!empty($args)) {
		if ($args[0][0] != '-') {
			break;
		}

		$flags = array_shift($args);
		$flags = str_split(substr($flags, 1));

		foreach ($flags as $flag) {
			switch ($flag) {
			case 'r':
				$recursive = true;
				break;
			case 'q':
				$print_names = false;
				break;
			default:
				fwrite(STDERR, "Unknown flag: $flag\n");
				usage();
				exit(1);
			}
		}
	}

	if (empty($args)) {
		fmt_stdin();
	}
	else {
		fmt_files($args, $recursive, $print_names);
	}
}

function fmt_stdin()
{
	$src = "";
	while (1) {
		$line = fgets(STDIN);
		if ($line === false) break;
		$src .= $line;
	}
	$src = fmt::format($src);
	echo $src;
}

function fmt_files($list, $recursive, $print_names)
{
	foreach ($list as $path) {
		if (!file_exists($path)) {
			fwrite(STDERR, "$path: no such file\n");
			continue;
		}
		if (is_file($path)) {
			$src = file_get_contents($path);
			$fmt = fmt::format($src);
			if ($fmt == $src) continue;
			if ($print_names) {
				echo $path, "\n";
			}
			file_put_contents($path, $fmt);
			continue;
		}
		if (!is_dir($path)) {
			fwrite(STDERR, "$path: unknown file type\n");
			continue;
		}
		if (!$recursive) continue;

		$sublist = ls($path);
		fmt_files($sublist, $recursive, $print_names);
	}
}

function ls($dir)
{
	$list = array();
	$d = opendir($dir);
	while (1) {
		$name = readdir($d);
		if ($name === false) break;
		if ($name[0] == '.') continue;
		$path = "$dir/$name";
		if (is_dir($path) || substr($name, -4) == '.php') {
			$list[] = $path;
		}
	}
	closedir($d);
	return $list;
}

?>
