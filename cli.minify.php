#!/usr/bin/php
<?php

    define('CLI', true);

    // Default argument count is 1 => filename of script
    // Check if no arguments or help arguments are specified
    if ($argc == 1 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {

?>

This is a commandline PHP script which minifies Javascript and CSS files.

  Usage:
  php <?php echo basename($argv[0]); ?> [options] [args]

  -d, --directory       One or more directories in which the files reside.
  -x, --exclude         One or more files to exclude from directory (see -d).
  -f, --file            One or more files to include.
                        Possibilities:
                            - Full path to filename.
                            - Relative to one of specified the dirs (see -d)
                            - Relative to the current working directory
                        In combination with the -d argument it's possible to
                        specify the order of minifying. Files will be minified
                        and concatenated in the order in which they are
                        specified.
  -i, --includeminified Use this argument to include minified files
                        (/(.|-)min./)) when reading a directory specified with
                        -d. Already minified files will only be concatenated.
  -o, --output          Output file
                        Possibilities:
                            - Full path to filename.
                            - Relative to the first specified directory (see -d)
                            - Relative to the current working directory
                        (Existing files will be overwritten!)
  -v, --verbose         More ouput.
  -h, --help            This help.
  --debug               Skip minifying, just concatenate all files.
  -c, --css             When not specifying files manually, use this argument
                        to filter for css files.
  -j, --javascript      When not specifying files manually, use this argument
                        to filter for css files.

<?php

} else {

    ini_set('memory_limit', '512M');    // Memory heavy action
    set_time_limit(0);                  // Large files can cause timeout
    require 'jsminplus.php';            // JsMinPlus 1.4
    require 'cli.functions.php';

    // Default values for arguments
    $verbose            = false; // Verbose output
    $debugNoMinify      = false; // No minifiying, only concatenating
    $includeMinified    = false; // When matching files in a dir, will include files with .min. in their name
    $minifyJavascript   = false;
    $minifyCSS          = false;

    $excludeFiles       = array(); // Files to exclude
    $inputDirs          = array(); // Directories to scan
    $inputFiles         = array(); // Manual input files
    $inputOutputFile    = ''; // Output file

    $prevOption         = ''; // User to store the previous argument

    // Handle arguments
    foreach ($argv AS $parameter) {
        if (substr($parameter, 0, 1) == '-') {
            // Arguments with no values
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
                case '-c':
                case '--css':
                    $minifyCSS          = true;
                break;
                case '-j':
                case '--javascript':
                    $minifyJavascript   = true;
                break;
                default:
                    $prevOption         = $parameter;
                break;
            }
        } else {
            // Values of arguments
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
                    // echo "\n";
                    // echo "Invalid argument $prevOption. See help for more information\n\n";
                    // echo "    php ". basename($argv[0]) ." -h\n\n";
                    // exit;
                break;
            }
        }
    }

    // Start of verbose outputting
    if ($verbose) {
        echo "\nSTARTING VERBOSE OUTPUT\n\n";
    }


    // For now it's not possible to minify both Javascript and CSS
    if ($minifyJavascript && $minifyCSS) {
        if ($verbose) {
            die("You cannot minify both Javascript or CSS files in one call.\n");
        }
        echo 0;
        exit;
    }

    // At least one of the types should be specified
    if (!$minifyJavascript && !$minifyCSS) {
        if ($verbose) {
            die("You must set a handling type, see -j and-c.\n");
        }
        echo 0;
        exit;
    }

    // Minified file pattern
    $minifiedFilePattern    = '(\.|-)min';

    // Containers file extentions, which will be used to filter file lists
    $fileExtentension   = '';

    // Set file extenion based on arguments
    if ($minifyJavascript) {
        $fileExtentension   = 'js';
    } elseif ($minifyCSS) {
        $fileExtentension   = 'css';
    }

    if ($verbose) {
        echo "MINIFY EXTENSION\n$fileExtentension\n\n";
    }


    if ($verbose) {
        echo "CREATING FULLPATHS FOR EXCLUDE FILES\n";
    }

    // Find fullpaths for exclude files
    $excludeFilesFull   = handleFilelists($excludeFiles, $verbose);


    $countDirs          = count($inputDirs);
    $countFiles         = count($inputFiles);
    $filesToHandle      = array(); // Will contain absolute paths

    // Check if any directories or files are given
    if ($countDirs == 0 && $countFiles == 0) {
        die("\nNothing to minify. No directories or files given.\n");
    }

    // Formatting purposes
    if ($verbose) {
        echo "\n";
    }

    if ($countFiles > 0) {

        $filesToHandle = handleFilelists($inputFiles, $verbose);
    }

    if ($countDirs > 0) {

        // Scan directory input for files
        foreach ($inputDirs AS $dir) {

            if (!is_dir($dir)) {

                if ($verbose) {
                    echo "$dir is not a valid directory.\n";
                }
                continue;
            } elseif (!is_readable($dir)) {

                if ($verbose) {
                    echo "You don't have the right permission to read $dir.\n";
                }
                continue;
            }

            // Read directory
            $filesInDir = scandir($dir);

            foreach ($filesInDir AS $file) {
                // Skip invalid entries
                if (
                    // Skip dots
                    $file == '.' ||
                    $file == '..' ||

                    // Exclude minified files, except when argument to include is specified
                    (!$includeMinified && preg_match('/'.$minifiedFilePattern.'\.'.$fileExtentension.'$/i', $file)) ||

                    // Exclude javascript files
                    // TODO: also add support for other filestypes (mainly CSS)
                    !preg_match('/\.'.$fileExtentension.'$/i', $file) ||

                    // Do not include directory when it's mentioned in exclude files
                    in_array($file, $excludeFiles)
                ) continue;

                // TODO: build in recursive directory check???

                // Add fullpaths of files to list
                $filesToHandle[]   = $dir . $file;
            }
        }
    }

    if ($verbose) {
        echo "FILES TO CONVERT\ntotal: ".count($filesToHandle)."\n".print_r($filesToHandle, true)."\n";
    }

    // TODO: file to full path converstion for $inputOutputFile, maybe via function?
    // Check if files should be merged into 1 file
    if (!empty($inputOutputFile)) {

        // try to find real path for output file
        $outputFileDir      = realpath(dirname($inputOutputFile));
        $outputFileName     = basename($inputOutputFile);

        // If directories are specified, and the user only send the clean
        // filename as value, then try to store the file in the first directory
        if ($countDirs >= 1 && $outputFileName == $inputOutputFile) {

            $outputFileDir      = realpath($inputDirs[0]);
        } elseif ($countFiles >= 1 && $outputFileName == $inputOutputFile) {

            $outputFileDir      = realpath(dirname($inputFiles[0]));
        }

        if (!is_dir($outputFileDir)) {

            if ($verbose) {
                echo "Directory $outputFileDir does not exist.\n";
            }

            die(0);
        } elseif (!is_writeable($outputFileDir)) {
            if ($verbose) {
                echo "You don't have the right permission to write to $outputFileDir.\n";
            }

            die(0);
        }

        $outputFile = $outputFileDir . '/' . $outputFileName;

        if ($verbose) {
            echo "GENERIC OUTPUTFILE\n".$outputFile."\n\n";
        }

        if (in_array($outputFile, $filesToHandle)) {

            // Remove outputfile from files to handle
            $key    = array_search($outputFile, $filesToHandle);
            unset($filesToHandle[$key]);

            if ($verbose) {
                echo "GENERIC OUTPUTFILE\nFile removed from minify process\n\n";
            }
        }
    }

    // Create and clear output file, when needed
    if (isset($outputFile) && !empty($outputFile)) {

        $status = file_put_contents($outputFile, '', LOCK_EX);

        if ($status === false) {
            if ($verbose) {
                echo "GENERIC OUTPUTFILE\nCould not create output file\n\n";
            }
            die(0);
        }

        if ($verbose) {
            echo "GENERIC OUTPUTFILE\nFile touched and trimmed\n\n";
        }
    }

    if ($verbose) {
        echo "START MINIFY PROCES\n\n";
    }


    $statusArray        = array(); // Contains statusses for each handled file
    $totalBytesWritten  = 0;

    // Start handling
    foreach ($filesToHandle AS $readFile) {

        // Check if file exists, double check doesn't hurt
        if (!is_readable($readFile)) {
            if ($verbose) {
                echo "skipped:\t\t".$readFile."\n\n";
            }
            continue;
        }

        // Check for writing to generic output file
        if (isset($outputFile) && !empty($outputFile)) {
            // One file

            $writeFile  = $outputFile; // Destination file
            $flags      = FILE_APPEND | LOCK_EX; // File writing options
            $seperator  = ';'; // Concatenation seperator
        } else {

            // Insert .min before the file extension
            $extension  = substr($readFile, strrpos($readFile, '.'));
            $cleanFile  = substr($readFile, 0, strrpos($readFile, '.'));
            $writeFile  = $cleanFile . '.min' . $extension; // Destination file
            $flags      = LOCK_EX; // File writing options
            $seperator  = ''; // No concatenation needed, therefore empty
        }

        if ($verbose) {
            echo "read:\t\t".$readFile."\n";
            echo "write:\t\t".$writeFile."\n";
            echo "type:\t\t".(($flags == LOCK_EX) ? 'new' : 'append')."\n";
        }

        // Retrieve contents of the unhandled file
        $orgContents    = file_get_contents($readFile);

        // Check for debug mode
        if ($debugNoMinify) {
            // Debug mode activated, no minifying

            $newContents = $orgContents;
        } else {

            // Only minify files that are not already minified
            if (preg_match('/'.$minifiedFilePattern.'\./i', $readFile) > 0) {

                $newContents    = $orgContents;
            } else {

                // Check for handling type
                if ($minifyJavascript) {

                    $newContents    = minifyJavascript($orgContents, $seperator);
                } elseif ($minifyCSS) {

                    $newContents    = minifyCss($orgContents);
                }
            }
        }

        // Write to file and store output in status array
        $success        = file_put_contents($writeFile, $newContents, $flags);
        $statusArray[]  = $success;

        // Increment totalbyteswritten on success
        if ($success !== false) {
            $totalBytesWritten += $success;
        }

        if ($verbose) {
            echo "minified:\t".((preg_match('/\.min/i', $readFile) > 0) ? 'false' : 'true')."\n";
            echo "status:\t\t".(($success) ? $success .' bytes written' : 'failed')."\n";
            echo "-------------------------------------------------------\n\n";
        }
    }

    // Check for errors in status array
    if (in_array(false, $statusArray)) {
        if ($verbose) {
            if ($debugNoMinify) {
                echo "ERROR\nAn error occured while concatenating\n";
            } else {
                echo "ERROR\nAn error occured while minifying\n";
            }
        } else {
            // Return boolean, so it can be used by automatic script handlers (e.g. IDE)
            echo 0;
        }
    } else {
        if ($verbose) {
            echo "DONE\n$totalBytesWritten bytes written\n";
            if ($debugNoMinify) {
                echo "All files are succesfully concatenated\n";
            } else {
                echo "All files are succesfully minified\n";
            }
        } else {
            // Return boolean, so it can be used by automatic script handlers (e.g. IDE)
            echo 1;
        }
    }
}

?>