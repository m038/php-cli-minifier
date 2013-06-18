<?php if (!defined('CLI')) die('CLI only.');


	/**
     * Find full filesystem path to a file
     * @param  string  $file            File as specified as argument
     * @param  mixed   $alternativepath If filepath cannot be found, use this
     *                                  parameter to try and determine path.
     *                                  Can be string for one path, or array for
     *                                  multiple path, stops on first found file
     * @param  boolean $verbose         More output
     * @return mixed                    Returns false on error and string on
     *                                  success
     */
    function findRealpath($file, $alternativepaths=array(), $verbose=false) {

        $filepath   = realpath($file);
        $filename   = basename($file);

        // Only filename is specified
        if (!$filepath) {

            // Convert to array
            if (is_string($alternativepaths)) {
                $alternativepaths = array($alternativepaths);
            }

            //
            foreach ($alternativepaths AS $path) {

                // Check for trailing slash
                if (substr($path, -1) != '/') {
                    $path .= '/';
                }

                $filepath = $path . $filename;

                if (is_file($filepath)) {
                    break;
                }
            }
        }

        // File does not exist
        if (!is_file($filepath)) {

            if ($verbose) {
                echo "$filepath is not a valid file.\n";
            }

            return false;
        } elseif (!is_readable($filepath)) {
            // file cannot be read by user

            if ($verbose) {
                echo "You don't have the right permission to read $filepath.\n";
            }

            return false;
        }

        return $filepath;
    }


    /**
     * Loop through array with files and convert filenames to file paths
     * @param  mixed    $fileList       List of files (array). A single file can
     *                                  also be specified as string, it will
     *                                  internally be converted to an array.
     * @param  array    $directoryPaths Contains a list of paths to directories
     *                                  in which will be scanned for files, when
     *                                  the file on itself cannot be found
     * @param  boolean  $verbose        More output
     * @return mixed                    Returns boolean on error and array on
     *                                  success
     */
    function handleFilelists($fileList, $directoryPaths=array(), $verbose=false) {

        // Check for type and data
        if ((!is_string($fileList) && !is_array($fileList)) || empty($fileList)) {

            if ($verbose) {

                if (!is_string($fileList) && !is_array($fileList)) {
                    echo "First parameter should be either of type string or array.\n";
                } elseif (empty($fileList)) {
                    echo "No file or filelist specified.\n";
                }
            }

            return false;
        }

        // Convert string to array
        if (!is_array($fileList)) {
            $fileList = array($fileList);
        }

        // Will contain list of files with full paths
        $fileListFullPaths  = array();
        $prevPath           = array(); // Store path of previous file

        foreach ($fileList AS $fileEntry) {

            // Try to find filepath
            $filepath = findRealpath($fileEntry, array_merge($prevPath, $directoryPaths));

            // Ignore file when no filepath is found
            if (!$filepath) {

                if ($verbose) {
                    echo "Skipping $fileEntry\n";
                }

                continue;
            }

            $fileListFullPaths[]    = $filepath;
            $prevPath[]             = dirname($filepath);
        }

        // Unique the array
        return array_unique($fileListFullPaths);
    }


    /**
     * Minifies CSS files
     * @param  $string $css CSS declarations
     * @return string       Minified CSS
     */
	function minifyCss($css) {
		$css 	= preg_replace('/[\t\n\r]/', '', $css); // Strip tabs and newlins
		$css 	= preg_replace('/\/\*.*?\*\//', '', $css); // Remove comments
		return $css;
	}


	/**
	 * Minifies Javascript code
	 * @param  string $javascript Javascript code
	 * @param  string $seperator  Seperator, since last ; is removed
	 * @return string             Minified Javascript code
	 */
	function minifyJavascript($javascript, $seperator='') {
		return JSMinPlus::minify($javascript).$seperator;
	}


?>