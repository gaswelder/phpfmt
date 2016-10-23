<?php
require "fmt/fmt.php";
require "fmt/out.php";
require "fmt/toks.php";

main($argv);

function usage()
{
	fwrite(STDERR, "Usage: phpfmt [-r] [files...]\n");
	fwrite(STDERR, "	-r	recurse into directories\n");
}

function main($args)
{
	$recursive = false;

	array_shift($args);
	while(!empty($args)) {
		if($args[0][0] != '-' || strlen($args[0]) != 2) {
			break;
		}

		$flag = array_shift($args);

		switch($flag) {
			case "-r":
				$recursive = true;
				break;
			default:
				fwrite(STDERR, "Unknown flag: $flag\n");
				usage();
				exit(1);
		}
	}

	if(empty($args)) {
		fmt_stdin();
	}
	else {
		fmt_files($args, $recursive);
	}
}

function fmt_stdin()
{
	$src = "";
	while(1) {
		$line = fgets(STDIN);
		if($line === false) break;
		$src .= $line;
	}
	$src = fmt::format($src);
	echo $src;
}

function fmt_files($list, $recursive)
{
	foreach($list as $path) {
		if(!file_exists($path)) {
			fwrite(STDERR, "$path: no such file\n");
			continue;
		}
		if(is_file($path)) {
			$src = file_get_contents($path);
			$src = fmt::format($src);
			file_put_contents($path, $src);
			continue;
		}
		if(!is_dir($path)) {
			fwrite(STDERR, "$path: unknown file type\n");
			continue;
		}
		if(!$recursive) continue;

		$list = glob("$path/*.php");
		fmt_files($list, $recursive);
	}
}


?>
