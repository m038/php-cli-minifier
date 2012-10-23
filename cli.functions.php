<?php if (!defined('CLI')) exit;

	function minifyCss($css) {
		$css 	= preg_replace('/[\t\n\r]/', '', $css); // Strip tabs and newlins
		$css 	= preg_replace('/\/\*.*?\*\//', '', $css); // Remove comments
		return $css;
	}

	function minifyJavascript($javascript, $seperator='') {
		require_once 'jsminplus.php';
		return JSMinPlus::minify($javascript).$seperator;
	}

?>