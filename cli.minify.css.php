#!/usr/bin/php
<?php

	//print_r($argv); exit;

	if ($argc == 1 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

  Usage:
  <?php echo $argv[0]; ?> [options] [args]

  -d    --directory One or more directories in which the javascript files
                    reside.
  -f    --file      One or more javascript files. Type:
                        - Full path to filename.
                        - Relative from one of the dirs (see -d)
                        - Filename inside one of the dirs (see -d)
                    If no argument specified, all files in --directory will
                    be parsed.
  -o    --output    Output file
                        - Full path to filename
                        - Relative for first dir (see -d)
                        - Filename in first dir (see -d)
                    (Existing files will be overwritten withouth asking!)
  -v    --verbose   Generate more explaining ouput.
  -h    --help      This help.

<?php
} else {

	ini_set('memory_limit', '512M'); 	// Memory heavy action
	set_time_limit(0); 					// Large files can cause timeout
	require 'jsminplus.php'; 			// Needed for minify class

	// Defaults
	$verbose			= false;
	$prevOption			= '';
	$inputDirs			= array();
	$inputFiles			= array();
	$inputOutputFile	= '';

	// Handle arguments
	foreach ($argv AS $parameter) {
		if (substr($parameter, 0, 1) == '-') {
			// Options with no parameters
			switch($parameter) {
				case '-v':
				case '--verbose':
					$verbose		= true;
				break;
				default:
					$prevOption		= $parameter;
				break;
			}
		} else {
			// Parameters to options
			switch($prevOption) {
				case '-d':
				case '--directory':
					$inputDirs[]		= $parameter;
				break;
				case '-f':
				case '--file':
					$inputFiles[]		= $parameter;
				break;
				case '-o':
				case '--output':
					$inputOutputFile	= $parameter;
				break;
				default:
					// error?
				break;
			}
		}
	}

	$countDirs	= count($inputDirs);
	$countFiles	= count($inputFiles);

	if ($countDirs == 0 && $countFiles == 0) {
		die("\nNothing to minify. (No directories of files specified.)\n");
	}

	if ($verbose) {
		echo "\n";
	}

	// Create list of files to read
	$filesToConvert	= array();
	if ($countFiles > 0) {
		foreach ($inputFiles AS $file) {
			$basename 	= basename($file);
			$dirname	= dirname($file);
			$fileOption	= 0;

			//
			if ($file != $basename) {
				if (substr($dirname, 0, 1) == '.') {
					$fileOption	= 1;
				} else {
					$fileOption	= 2;
				}
			}

			// create for each dir and file an entry
			if ($countDirs) {
				foreach ($inputDirs AS $dir) {
					switch ($fileOption) {
						case 0:
							$filesToConvert[]	= $dir . $file;
						break;
						case 1:
							$filesToConvert[]	= $dir . $file;
						break;
						case 2:
							$filesToConvert[]	= $file;
						break;
					}
				}
			} else {
				$dir	= dirname(__FILE__);
				switch ($fileOption) {
					case 0:
						$filesToConvert[]	= $dir . $file;
					break;
					case 1:
						$filesToConvert[]	= $dir . $file;
					break;
					case 2:
						$filesToConvert[]	= $file;
					break;
				}
			}
		}
	} else {
		foreach ($inputDirs AS $dir) {
			$filesInDir	= scandir($dir);
			foreach ($filesInDir AS $file) {
				// Skip dots
				if (
					$file == '.' ||
					$file == '..' ||
					preg_match('/\.min\.js$/i', $file) ||
					!preg_match('/\.js$/i', $file)
				) continue;

				$filesToConvert[]	= $dir . $file;
			}
		}
	}

	if ($verbose) {
		echo "FILES TO CONVERT\ntotal: ".count($filesToConvert)."\n".print_r($filesToConvert, true)."\n\n";
	}

	// Check if files should be merged into 1 file
	if (!empty($inputOutputFile)) {

		// Output dir
		if (count($inputDirs) == 0) {
			if (count($inputFiles)) {
				// TODO: fix this shit
			} else {
				$dir	= dirname(__FILE__);
			}
		} else {
			$outputDir	= $inputDirs[0]; // If multiple, pick first
		}

		$basename 	= basename($inputOutputFile);
		$dirname	= dirname($inputOutputFile);

		//
		if ($inputOutputFile != $basename) {
			if (substr($dirname, 0, 1) == '.') {
				// File contains relative path
				$outputFile	= $outputDir . $inputOutputFile;
			} else {
				// File contains fullpath
				$outputFile	= $inputOutputFile;
			}
		} else {
			// File containers no path
			$outputFile		= $outputDir . $inputOutputFile;
		}

		if ($verbose) {
			echo "GENERIC OUTPUTFILE\n".$outputFile."\n\n";
		}

		if (in_array($outputFile, $filesToConvert)) {

			$key	= array_search($outputFile, $filesToConvert);
			unset($filesToConvert[$key]);

			if ($verbose) {
				echo "GENERIC OUTPUTFILE\nFile removed from minify process\n\n";
			}
		}
	}

	// Clear output file
	if (isset($outputFile) && !empty($outputFile)) {

		$status = file_put_contents($outputFile, '', LOCK_EX);

		if ($verbose) {
			echo "GENERIC OUTPUTFILE\nFile touched and trimmed\n\n";
		}
	}

	if ($verbose) {
		echo "START MINIFY PROCES\n\n";
	}

	$statusArray	= array();

	// Startconversion
	foreach ($filesToConvert AS $readFile) {

		// Check if file exists
		if (!is_readable($readFile)) {
			if ($verbose) {
				echo "skipped:\t\t".$readFile."\n\n";
			}
			continue;
		}

		if (isset($outputFile) && !empty($outputFile)) {
			// Generic input/output file
			$writeFile	= $outputFile;
			$flags		= FILE_APPEND | LOCK_EX;
			$seperator	= ';';
		} else {
			$extension	= substr($readFile, strrpos('.'));
			$cleanFile	= substr($readFile, 0, strrpos('.'));
			$writeFile	= $cleanFile . '.min' . $extension;
			$flags		= LOCK_EX;
			$seperator	= '';
		}

		if ($verbose) {
			echo "read:\t\t".$readFile."\n";
			echo "write:\t\t".$writeFile."\n";
			echo "type:\t\t".(($flags == LOCK_EX) ? 'new' : 'append')."\n";
		}

		$orgContents	= file_get_contents($readFile);

		// check if main file is written
		$tmpContents 	= preg_replace('/[\t\n\r]/', '', $orgContents);
		$tmpContents 	= preg_replace('/\/\*.*?\*\//', '', $tmpContents);

		$newContents 	= $tmpContents;
		$succes			= file_put_contents($writeFile, $newContents, $flags);
		$statusArray[]	= $succes;

		if ($verbose) {
			echo "status:\t\t".(($succes) ? $succes .' bytes written' : 'failed')."\n";
			echo "-------------------------------------------------------\n\n";
		}
	}

	if (in_array(false, $statusArray)) {
		if ($verbose) {
			echo "ERROR\nAn error occured during the minify process\n";
		} else {
			echo 0;
		}
	} else {
		if ($verbose) {
			echo "DONE\nAll files were succesfully minified\n";
		} else {
			echo 1;
		}
	}
}

?>