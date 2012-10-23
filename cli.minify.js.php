#!/usr/bin/php
<?php

    //print_r($argv); exit;

    if ($argc == 1 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

This is a commandline PHP script which minifies Javascript files.

  Usage:
  php <?php echo basename($argv[0]); ?> [options] [args]

  -d    --directory One or more directories in which the files reside.
  -x    --exclude   One or more files to exclude from directory (see -d).
  -f    --file      One or more files to include.
                    Possibilities:
                        - Full path to filename.
                        - Relative to one of specified the dirs (see -d)
                        - Relative to the current working directory
                    In combination with the -d argument it's possible to
                    specify the order of minifying. Files will be minified and
                    concatenated in the order in which they are specified.
  -i
  --includeminified Use this argument to include minified files (/(.|-)min./))
                    when reading a directory specified with -d.
                    Already minified files will only be concatenated.
  -o    --output    Output file
                    Possibilities:
                        - Full path to filename.
                        - Relative to the first specified directory (see -d)
                        - Relative to the current working directory
                    (Existing files will be overwritten!)
  -v    --verbose   More ouput.
  -h    --help      This help.
  --debug           Skip minifying, just concatenate all files.

<?php
} else {

    ini_set('memory_limit', '512M');    // Memory heavy action
    set_time_limit(0);                  // Large files can cause timeout
    require 'jsminplus.php';            // JsMinPlus 1.4

    // Defaults
    $verbose            = false; // Verbose output
    $debugNoMinify      = false; // No minifiying, only concatenating
    $includeMinified    = false; // When matching files in a dir, will include files with .min. in their name

    $excludeFiles       = array(); // Files to exclude
    $inputDirs          = array(); // Directories to scan
    $inputFiles         = array(); // Manual input files
    $inputOutputFile    = ''; // Output file

    $prevOption         = ''; // User to store the previous option

    // Handle arguments
    foreach ($argv AS $parameter) {
        if (substr($parameter, 0, 1) == '-') {
            // Options with no parameters
            switch($parameter) {
                case '-v':
                case '--verbose':
                    $verbose            = true;
                break;
                case '-i':
                case '--includeminified':
                    $includeMinified    = true;
                break;
                case '--debug':
                    $debugNoMinify      = true;
                break;
                default:
                    $prevOption         = $parameter;
                break;
            }
        } else {
            // Parameters to options
            switch($prevOption) {
                case '-d':
                case '--directory':
                    $inputDirs[]        = $parameter;
                break;
                case '-x':
                case '--exclude':
                    $excludeFiles[]     = $parameter;
                break;
                case '-f':
                case '--file':
                    $inputFiles[]       = $parameter;
                break;
                case '-o':
                case '--output':
                    $inputOutputFile    = $parameter;
                break;
                default:
                    // Display help about to user parameters?
                break;
            }
        }
    }

    $countDirs  = count($inputDirs);
    $countFiles = count($inputFiles);

    if ($countDirs == 0 && $countFiles == 0) {
        die("\nNothing to minify. (No directories of files specified.)\n");
    }

    if ($verbose) {
        echo "\n";
    }

    // Create list of files to read
    $filesToConvert = array();
    if ($countFiles > 0) {

        foreach ($inputFiles AS $file) {

            $filepath = realpath($file);

            // Check if full path is given, else try to create it from previous files
            if ($filepath == $file) {
                //
            }

            // Skip file if it doesn't exist
            if (!is_file($filepath)) {
                if ($verbose) echo "File $file does not exist.\n";
                continue;
            }

            if (in_array($file, $excludeFiles)) {
                if ($verbose) echo "Excluding $file, match found in exclude parameter.\n";
                continue;
            }

            $basename   = basename($file);
            $dirname    = dirname($filepath);
            $fileOption = 0;

            //
            if ($file != $basename) {
                if (substr($dirname, 0, 1) == '.') {
                    $fileOption = 1;
                } else {
                    $fileOption = 2;
                }
            }

            // create for each dir and file an entry
            if ($countDirs) {
                foreach ($inputDirs AS $dir) {
                    switch ($fileOption) {
                        case 0:
                            $filesToConvert[]   = $dir . $file;
                        break;
                        case 1:
                            $filesToConvert[]   = $dir . $file;
                        break;
                        case 2:
                            $filesToConvert[]   = $file;
                        break;
                    }
                }
            } else {
                $dir    = dirname(__FILE__);
                switch ($fileOption) {
                    case 0:
                        $filesToConvert[]   = $dir . $file;
                    break;
                    case 1:
                        $filesToConvert[]   = $dir . $file;
                    break;
                    case 2:
                        $filesToConvert[]   = $file;
                    break;
                }
            }
        }
    }

    if ($countDirs > 0) {
        foreach ($inputDirs AS $dir) {
            $filesInDir = scandir($dir);
            foreach ($filesInDir AS $file) {
                // Skip dots
                if (
                    $file == '.' ||
                    $file == '..' ||
                    (!$includeMinified && preg_match('/\.min\.js$/i', $file)) ||
                    !preg_match('/\.js$/i', $file) ||
                    in_array($file, $excludeFiles)
                ) continue;

                $filesToConvert[]   = $dir . $file;
            }
        }
    }

    if ($verbose) {
        echo "FILES TO CONVERT\ntotal: ".count($filesToConvert)."\n".print_r($filesToConvert, true)."\n\n";
        // remove line below, when done testing
        exit;
    }

    // Check if files should be merged into 1 file
    if (!empty($inputOutputFile)) {

        // Output dir
        if (count($inputDirs) == 0) {
            if (count($inputFiles)) {
                // TODO: fix this shit
            } else {
                $dir    = dirname(__FILE__);
            }
        } else {
            $outputDir  = $inputDirs[0]; // If multiple, pick first
        }

        $basename   = basename($inputOutputFile);
        $dirname    = dirname($inputOutputFile);

        //
        if ($inputOutputFile != $basename) {
            if (substr($dirname, 0, 1) == '.') {
                // File contains relative path
                $outputFile = $outputDir . $inputOutputFile;
            } else {
                // File contains fullpath
                $outputFile = $inputOutputFile;
            }
        } else {
            // File containers no path
            $outputFile     = $outputDir . $inputOutputFile;
        }

        if ($verbose) {
            echo "GENERIC OUTPUTFILE\n".$outputFile."\n\n";
        }

        if (in_array($outputFile, $filesToConvert)) {

            $key    = array_search($outputFile, $filesToConvert);
            unset($filesToConvert[$key]);

            if ($verbose) {
                echo "GENERIC OUTPUTFILE\nFile removed from minify process\n\n";
            }
        }
    }

    // Clear output file
    if (isset($outputFile) && !empty($outputFile)) {

        $status = file_put_contents($outputFile, '', LOCK_EX);

        //if (!$status) {
        //  echo "GENERIC OUTPUTFILE\nCould not create output file\n\n";
        //  echo 1; exit;
        //}

        if ($verbose) {
            echo "GENERIC OUTPUTFILE\nFile touched and trimmed\n\n";
        }
    }

    if ($verbose) {
        echo "START MINIFY PROCES\n\n";
    }

    $statusArray    = array();

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
            $writeFile  = $outputFile;
            $flags      = FILE_APPEND | LOCK_EX;
            $seperator  = ';';
        } else {
            $extension  = substr($readFile, strrpos($readFile, '.'));
            $cleanFile  = substr($readFile, 0, strrpos($readFile, '.'));
            $writeFile  = $cleanFile . '.min' . $extension;
            $flags      = LOCK_EX;
            $seperator  = '';
        }

        if ($verbose) {
            echo "read:\t\t".$readFile."\n";
            echo "write:\t\t".$writeFile."\n";
            echo "type:\t\t".(($flags == LOCK_EX) ? 'new' : 'append')."\n";
        }

        $orgContents    = file_get_contents($readFile);
        if ($debugNoMinify) {
            $newContents = $orgContents;
        } else {
            // Only minify not already minified files
            $newContents    = (preg_match('/(?:\.|-)min\./i', $readFile) > 0)
                            ? $orgContents
                            : JSMinPlus::minify($orgContents).$seperator;
        }

        $succes         = file_put_contents($writeFile, $newContents, $flags);
        $statusArray[]  = $succes;

        if ($verbose) {
            echo "minified:\t".((preg_match('/\.min/i', $readFile) > 0) ? 'false' : 'true')."\n";
            echo "status:\t\t".(($succes) ? $succes .' bytes written' : 'failed')."\n";
            echo "-------------------------------------------------------\n\n";
        }
    }

    if (in_array(false, $statusArray)) {
        if ($verbose) {
            if ($debugNoMinify) {
                echo "ERROR\nAn error occured while concatenating\n";
            } else {
                echo "ERROR\nAn error occured while minifying\n";
            }
        } else {
            echo 0;
        }
    } else {
        if ($verbose) {
            if ($debugNoMinify) {
                echo "DONE\nAll files were succesfully concatenated\n";
            } else {
                echo "DONE\nAll files were succesfully minified\n";
            }
        } else {
            echo 1;
        }
    }
}

?>