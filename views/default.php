<table class="taglist" id="digium_addons">
<thead>
	<tr>
		<th>Addon</th>
		<th>Purchase</th>
		<th>Installed</th>
		<th>Registration</th>
		<th>License Backup</th>
		<th>Documentation</th>
	</tr>
</thead>
<tbody>
<?php 	$output = array();
	$addons = $digium_addons->get_addons();
	if (count($addons) < 1) {
		$output[] = "		<tr>
			<td colspan=\"4\">No addons available.</td>
		</tr>";
	} else {
		foreach ($addons as $addon) {
			$actions = array();
			if ($addon['is_registered']) {
				$actions['backup'] = array(
					'Backup'=>'config.php?type=setup&display=digiumaddons&page=backup&addon='.$addon['id']
				);
			} else {
				$actions['backup'] = array(
					'none'=>'#'
				);
			}

			if ($addon['is_installed']) {
				$actions['install'] = array(
					'Yes'=>'#'
				);
			} else if (empty($addon['installation'])) {
				$actions['install'] = array(
					'See Docs'=>$addon['documentation']
				);
			} else {
				$actions['install'] = array(
					'Install'=>$addon['installation']
				);
			}

			if (count($addon['registers']) > 0) {
				$actions['register'] = array(
					'Add License'=>'config.php?type=setup&display=digiumaddons&page=add-license-form&addon='.$addon['id']
				);
			} else if ($addon['register_limit'] == 0 || count($addon['registers']) < $addon['register_limit']) {
				$actions['register'] = array(
					'Register'=>'config.php?type=setup&display=digiumaddons&page=add-license-form&addon='.$addon['id']
				);
			} else {
				$actions['register'] = array(
					'Maxed-Registrations'=>'#'
				);
			}

			$act_output = array();
			foreach (array('install', 'register', 'backup') as $act) {
				$act_output[$act] = array();
				foreach ($actions[$act] as $txt=>$link) {
					if ($link == '#') {
						$act_output[$act][] = $txt;
					} else {
						$act_output[$act][] = "<a href=\"{$link}\"><span>{$txt}</span></a>";
					}
				}
				$act_output[$act] = implode("\n", $act_output[$act]);
			}

			$output[] = "		<tr>
				<td>{$addon['name']}</td>
				<td><a href=\"{$addon['link']}\" target=\"_blank\">Purchase</a></td>
				<td>{$act_output['install']}</td>
				<td>{$act_output['register']}</td>
				<td>{$act_output['backup']}</td>
				<td><a href=\"{$addon['documentation']}\" target=\"_blank\">Documentation</a></td>
			</tr>";
		} 
	}
	echo implode("\n", $output);
?>
</tbody>
</table>
