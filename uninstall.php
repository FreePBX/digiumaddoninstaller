<?php

global $db;
global $asterisk_conf;

$tables = array('digiumaddoninstaller_system', 
	'digiumaddoninstaller_addons', 
	'digiumaddoninstaller_downloads', 
	'digiumaddoninstaller_addons_downloads', 
	'digiumaddoninstaller_downloads_bits', 
	'digiumaddoninstaller_downloads_ast_versions', 
	'digiumaddoninstaller_registers'
);
foreach ($tables as $table) {
	$sql = "DROP TABLE IF EXISTS {$table}";
	$result = $db->query($sql);
	if (DB::IsError($result)) {
		die_freepbx($result->getDebugInfo());
	}
	unset($result);
}

// end of file
