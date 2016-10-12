#!/usr/bin/env php
<?php
/**
 * @author	cytopia <cytopia@everythingcli.org>
 * @date	2016-11-12
 * @version	v0.1
 *
 * Usage: $0 path lang1 [lang2 [lang3 ...]]
 *
 */

$CLR_RED="\033[0;31m";
$CLR_GRN="\033[0;32m";
$CLR_YEL="\033[0;33m";
$CLR_BLU="\033[0;34m";
$CLR_RST="\033[0m";



/********************************************************************************
 * Functions
 ********************************************************************************/
/**
 * Executes shell commands on the PHP-FPM Host
 *
 * @param  string $cmd    [description]
 * @param  string $output [description]
 * @return integer
 */
function my_exec($cmd, &$output = '')
{
	// Clean output
	$output = '';
	exec($cmd, $output, $exit_code);
	return $exit_code;
}

function print_usage()
{
	echo 'Usage: '.$GLOBALS['argv'][0].' <path> <lang1> [<lang2> [<lang3> ...]]'."\n";
}


function get_input_languages()
{
	$languages = array();
	for ($i=2; $i<$GLOBALS['argc']; $i++) {
		$languages[] = $GLOBALS['argv'][$i];
	}
	return $languages;
}

function get_message_directories()
{
	$directories = array();
	my_exec('find '.$GLOBALS['argv'][1].' -type d -name messages', $directories);
	return $directories;
}


/********************************************************************************
 * Check Functions
 ********************************************************************************/
/**
 * data[dir-pair]['PATH'] = TRUE|FALSE
 */
function check_message_lang_dirs($message_dirs, $languages)
{
	$data = array();

	foreach ($message_dirs as $dir) {
		foreach ($languages as $lang) {
			$lang_dir = $dir.DIRECTORY_SEPARATOR.$lang;
			if (is_dir($lang_dir)) {
				$data[$dir][$lang_dir] = TRUE; // Exists
			} else {
				$data[$dir][$lang_dir] = FALSE; // Missing
			}
		}
	}
	return $data;
}

/**
 * data[PATH_TO_MESSAGE_DIR] = array(file1, file2, file3)
 */
function get_all_expected_language_files($message_dirs, $languages)
{
	$data = array();
	foreach ($message_dirs as $dir) {
		foreach ($languages as $lang) {
			$path = $dir.DIRECTORY_SEPARATOR.$lang;
			if (!isset($data[$dir])) {
				$data[$dir] = scandir($path);
			} else {
				$data[$dir] = array_merge($data[$dir], scandir($path));
			}
		}
		// Make unique (as we have stored files from all language subdirs)
		$data[$dir] = array_unique($data[$dir]);
		// Remove '..' dir
		if (($key = array_search('..', $data[$dir])) !== false) {
			unset($data[$dir][$key]);
		}
		// Remove '.' dir
		if (($key = array_search('.', $data[$dir])) !== false) {
			unset($data[$dir][$key]);
		}
	}
	return $data;
}



/********************************************************************************
 * Checks
 ********************************************************************************/

if ($argc < 3) {
	echo "Invalid number of arguments.\n";
	print_usage();
	exit(1);
}

if (!is_dir($argv[1])) {
	echo $argv[1]." is not a directory.\n";
	print_usage();
	exit(1);
}


/********************************************************************************
 * Fill variables
 ********************************************************************************/

$languages		= get_input_languages();
$message_dirs	= get_message_directories();




/********************************************************************************
 * Checks
 ********************************************************************************/

// ------------------------------------------------------------------------------
// 01 Check if all message dirs each have all language dirs inside
// ------------------------------------------------------------------------------

$data	= check_message_lang_dirs($message_dirs, $languages);
$errno	= 0;
printf($CLR_YEL.'%s'.$CLR_RST."\n", "--------------------------------------------------------------------------------");
printf($CLR_YEL.'%s'.$CLR_RST."\n", "- (1/3) Checking directories pairs");
printf($CLR_YEL.'%s'.$CLR_RST."\n\n", "--------------------------------------------------------------------------------");
foreach ($data as $pair) {
	foreach ($pair as $dir => $ok) {
		if ($ok) {
			echo $CLR_GRN.'[X] Found:   '.$dir.$CLR_RST."\n";
		} else {
			echo $CLR_RED.'[E] Missing: '.$dir.$CLR_RST."\n";
			$errno++;
		}
	}
	echo "\n"; // Newline to separate groups
}

if ($errno) {
	echo $CLR_RED.'[FAIL] '.$errno.' missing language folder(s).'.$CLR_RST."\n";
	echo $CLR_RED.'==> Aborting...'.$CLR_RST."\n";
	exit(1);
} else {
	echo $CLR_GRN.'[PASS] All subdirectories have all language folders.'.$CLR_RST."\n\n";
}


// ------------------------------------------------------------------------------
// 02 Check if all possible lang files are present in each lang folder
// ------------------------------------------------------------------------------

$language_files	= get_all_expected_language_files($message_dirs, $languages);
$errno	= 0;
printf($CLR_YEL.'%s'.$CLR_RST."\n", "--------------------------------------------------------------------------------");
printf($CLR_YEL.'%s'.$CLR_RST."\n", "- (2/3) Check if all possible lang files are present in each lang folder");
printf($CLR_YEL.'%s'.$CLR_RST."\n\n", "--------------------------------------------------------------------------------");
foreach ($language_files as $message_dir => $files) {
	foreach ($files as $file) {
		foreach ($languages as $language) {
			$language_file = $message_dir.DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.$file;
			if (!is_file($language_file)) {
				echo '[E] File not found: '.$language_file."\n";
				$errno++;
			}
		}
	}
}

if ($errno) {
	echo "\n";
	echo $CLR_RED.'[FAIL] '.$errno.' language file(s) missing'.$CLR_RST."\n";
	echo $CLR_RED.'==> Aborting...'.$CLR_RST."\n";
	exit(1);
} else {
	echo $CLR_GRN.'[PASS] All files exist.'.$CLR_RST."\n\n";
}



// ------------------------------------------------------------------------------
// 03 Validate Language file indicces
// ------------------------------------------------------------------------------

$errno	= 0;
printf($CLR_YEL.'%s'.$CLR_RST."\n", "--------------------------------------------------------------------------------");
printf($CLR_YEL.'%s'.$CLR_RST."\n", "- (3/3) Validate PHP Array indices");
printf($CLR_YEL.'%s'.$CLR_RST."\n\n", "--------------------------------------------------------------------------------");
if (count($languages) == 1) {
	echo $CLR_BLU.'[SKIP] Only one language specified, so no other language indices to compare against.'.$CLR_RST."\n";
	exit(0);
}


$combinations = array(); // keep track of already checked combinations!
// Do this for every single message directory
foreach ($language_files as $message_dir => $files) {

	// Do this for every language file inside a message directory
	foreach ($files as $file) {

		// Each message directory should have all language
		// directories, so loop over each language directory.
		foreach ($languages as $lang) {

			// This is the current language that we are going to
			// compare against all others
			$curr_lang	= $lang;

			// Get all other languages by removing the current
			// language from the languages array
			$other_langs= array_diff($languages, array($curr_lang));


			// Loop over other languages
			foreach ($other_langs as $next_lang) {
				// For easier readability, name them 1 and 2
				$lang1 = $curr_lang;
				$lang2 = $next_lang;


				$file1 = $message_dir.DIRECTORY_SEPARATOR.$lang1.DIRECTORY_SEPARATOR.$file;
				$file2 = $message_dir.DIRECTORY_SEPARATOR.$lang2.DIRECTORY_SEPARATOR.$file;

				// Read PHP arrays of a language file of two different languages
				$arr1 = (include $file1);
				$arr2 = (include $file2);

				// Check the two php included data for correctness
				// If this happends, the file is fucked up.
				if (!is_array($arr1)) {
					echo '[FATAL] '.$file1.' does not contain a valid PHP array - File is fucked'."\n";
					$errno++;
					continue; // restart the loop for the next round
				}
				if (!is_array($arr2)) {
					echo '[FATAL] '.$file2.' does not contain a valid PHP array - File is fucked'."\n";
					$errno++;
					continue; // restart the loop for the next round
				}



				// Check if file1 does not have keys which are in file2
				$f1_ern = 0;
				echo '==> '.$file1.' ... ';
				foreach ($arr2 as $key2 => $val2) {
					// Check if this combination has already been checked?
					if (!isset($combinations[$file1][$file2][$key2])) {
						$combinations[$file1][$file2][$key2] = TRUE;
						if (!isset($arr1[$key2])) {
							if ($f1_ern == 0) {
								echo $CLR_RED.'ERROR'.$CLR_RST."\n";
							}
							echo '[E] '.$lang1.' Missing key '.$CLR_YEL."'".$key2."'".$CLR_RST.' (found in: '.$lang2.')'."\n";
							$f1_ern++;
							$errno++;
						}
					}
				}
				if (!$f1_ern) {
					echo $CLR_GRN.'OK'.$CLR_RST."\n";
				}

				// Check if file2 does not have keys which are in file1
				$f2_ern = 0;
				echo '==> '.$file2.' ... ';
				foreach ($arr1 as $key1 => $val1) {
					// Check if this combination has already been checked?
					if (!isset($combinations[$file2][$file1][$key1])) {
						$combinations[$file2][$file1][$key1] = TRUE;
						if (!isset($arr2[$key1])) {
							if ($f2_ern == 0) {
								echo $CLR_RED.'ERROR'.$CLR_RST."\n";
							}
							echo '[E] '.$lang2.' Missing key '.$CLR_YEL."'".$key1."'".$CLR_RST.' (found in: '.$lang1.')'."\n";
							$f2_ern++;
							$errno++;
						}
					}
				}
				if (!$f2_ern) {
					echo $CLR_GRN.'OK'.$CLR_RST."\n";
				}
			}
		}
	}
}


if ($errno) {
	echo "\n";
	echo $CLR_RED.'[FAIL] '.$errno.' language file(s) have different indices'.$CLR_RST."\n";
	echo $CLR_RED.'==> Aborting...'.$CLR_RST."\n";
	exit(1);
} else {
	echo $CLR_GRN.'[PASS] All files have correct indices.'.$CLR_RST."\n\n";
}



