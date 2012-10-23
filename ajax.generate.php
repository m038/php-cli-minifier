<?php

	ini_set("memory_limit","512M");
	set_time_limit(0);
	require 'jsminplus.php';

	$script = html_entity_decode(stripslashes($_REQUEST['minifyContent']));
	$script = JSMinPlus::minify($script);

	echo $script.';';

?>