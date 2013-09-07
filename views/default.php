<table class="taglist" id="digium_addons">
<thead>
	<tr>
		<th>Addon</th>
		<th>Purchase</th>
		<th>Installation</th>
		<th>Registration</th>
		<th>Backup</th>
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
					'NoBackup'=>'#'
				);
			}

			//case 'installed':
			if ($addon['is_installed'] && $addon['is_uptodate']) {
				$actions['install'] = array(
					'Uninstall'=>'config.php?type=setup&display=digiumaddons&page=uninstall&addon='.$addon['id']
				);
			//case 'update_available':
			} else if ($addon['is_installed'] && !$addon['is_uptodate']) {
				$actions['install'] = array(
					'Update'=>'config.php?type=setup&display=digiumaddons&page=update&addon='.$addon['id'],
					'Uninstall'=>'config.php?type=setup&display=digiumaddons&page=uninstall&addon='.$addon['id']
				);
			//case 'not_installed':
			} else if ( ! $addon['is_installed']) {
				$actions['install'] = array(
					'Install'=>'config.php?type=setup&display=digiumaddons&page=install&addon='.$addon['id']
				);
			}

			if ($addon['register_limit'] == 0  || count($addon['registers']) < $addon['register_limit']) {
				$actions['register'] = array(
					'Add-License'=>'config.php?type=setup&display=digiumaddons&page=add-license-form&addon='.$addon['id']
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
					if ($txt == 'Maxed-Registrations') {
						$act_output[$act][] = "Max Registrations";
					} else if ($txt == 'NoBackup') {
						$act_output[$act][] = "none";
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
