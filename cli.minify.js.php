#!/usr/bin/php
<?php

    // TODO: change all file handlings to absolute paths

    /*
        File handling
        Always try to determine full paths for file for further use in this script.
        Different scenario's:
          - Full path
            Use this full path
          - No path is specified
            Check if there is a previous file in this list with a full path
            (either full path directly from the argument value, or by determinaton)
            Check if file exists in current directory
          - Relative path is specified

        File not found
        Return script terminating error
    */

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

    $countDirs      = count($inputDirs);
    $countFiles     = count($inputFiles);
    $filesToHandle  = array(); // Will contain absolute paths

    // Check if any directories or files are given
    if ($countDirs == 0 && $countFiles == 0) {
        die("\nNothing to minify. No directories or files given.\n");
    }

    // Formatting purposes
    if ($verbose) {
        echo "\n";
    }


    if ($countFiles > 0) {

        // Scan file input
        foreach ($inputFiles AS $file) {

            // Try to automaticly create absolute path
            $filepath = realpath($file);

            // If fullpath is the same as file, probably file cannot be found
            if ($filepath == $file) {
                // TODO: try to find file based on previous directory
            }

            // Skip file if it doesn't exist
            if (!is_file($filepath)) {
                if ($verbose) echo "File $file does not exist.\n";
                continue;
            }

            // Skip file if in exclude list
            // TODO: create absolute paths in exclude list
            if (in_array($file, $excludeFiles)) {
                if ($verbose) echo "Excluding $file, match found in exclude parameter.\n";
                continue;
            }

            $basename   = basename($file);
            $dirname    = dirname($filepath);
            $fileOption = 0;

            // TODO: extract directory from path for next loop
            // TODO: add filepath to array

            /*
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
                            $filesToHandle[]   = $dir . $file;
                        break;
                        case 1:
                            $filesToHandle[]   = $dir . $file;
                        break;
                        case 2:
                            $filesToHandle[]   = $file;
                        break;
                    }
                }
            } else {
                $dir    = dirname(__FILE__);
                switch ($fileOption) {
                    case 0:
                        $filesToHandle[]   = $dir . $file;
                    break;
                    case 1:
                        $filesToHandle[]   = $dir . $file;
                    break;
                    case 2:
                        $filesToHandle[]   = $file;
                    break;
                }
            }
            */
        }
    }

    if ($countDirs > 0) {

        // Scan directory input for files
        foreach ($inputDirs AS $dir) {

            // TODO: check if directory exists && is_readable

            // Read directory
            $filesInDir = scandir($dir);

            foreach ($filesInDir AS $file) {
                // Skip invalid entries
                if (
                    // Skip dots
                    $file == '.' ||
                    $file == '..' ||
                    // Exclude minified files, except when argument to include is specified
                    (!$includeMinified && preg_match('/\.min\.js$/i', $file)) ||
                    // Exclude javascript files
                    // TODO: also add support for other filestypes (mainly CSS)
                    !preg_match('/\.js$/i', $file) ||
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
        echo "FILES TO CONVERT\ntotal: ".count($filesToHandle)."\n".print_r($filesToHandle, true)."\n\n";
        // remove line below, when done testing
        exit;
    }

    // TODO: file to full path converstion for $inputOutputFile, maybe via function?
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
            // File contains no path
            $outputFile     = $outputDir . $inputOutputFile;
        }

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

    // Start handling
    foreach ($filesToHandle AS $readFile) {

        // Check if file exists
        // TODO: double check can be removed, right? But maybe good to keep anyway...
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

            // Insert .min before the file extention
            $extension  = substr($readFile, strrpos($readFile, '.'));
            $cleanFile  = substr($readFile, 0, strrpos($readFile, '.'));
            $writeFile  = $cleanFile . '.min' . $extension; // Destination file
            $flags      = LOCK_EX; // File writing options
            $seperator  = ''; // No concatenation needed, therefor empty
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

            // Only minify files that should not be minified
            $newContents    = (preg_match('/(?:\.|-)min\./i', $readFile) > 0)
                            ? $orgContents
                            : JSMinPlus::minify($orgContents).$seperator;
        }

        // Write to file and store output in status array
        $succes         = file_put_contents($writeFile, $newContents, $flags);
        $statusArray[]  = $succes;

        if ($verbose) {
            echo "minified:\t".((preg_match('/\.min/i', $readFile) > 0) ? 'false' : 'true')."\n";
            echo "status:\t\t".(($succes) ? $succes .' bytes written' : 'failed')."\n";
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
            if ($debugNoMinify) {
                echo "DONE\nAll files are succesfully concatenated\n";
            } else {
                echo "DONE\nAll files are succesfully minified\n";
            }
        } else {
            // Return boolean, so it can be used by automatic script handlers (e.g. IDE)
            echo 1;
        }
    }
}

?>