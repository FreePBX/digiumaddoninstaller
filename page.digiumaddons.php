<?php

/**
 * FreePBX DAHDi Config Module
 *
 * Copyright (c) 2009, Digium, Inc.
 *
 * Author: Ryan Brindley <ryan@digium.com>
 *
 * This program is free software, distributed under the terms of
 * the GNU General Public License Version 2. 
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
if(!extension_loaded('digium_register') && function_exists('dl')) {
	if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
		if (!dl('php_digium_register.dll')) return;
	} else {
		// PHP_SHLIB_SUFFIX gives 'dylib' on MacOS X but modules are 'so'.
		if (PHP_SHLIB_SUFFIX === 'dylib') {
			if (!dl('digium_register.so')) return;
		} else {
			if (!dl('digium_register.'.PHP_SHLIB_SUFFIX)) return;
		}
	}
}
global $db;
if (extension_loaded('digium_register')) {
	$page = (isset($_GET['page'])) ? $_GET['page'] : 'default';
	$digium_addons = new digium_addons();
	$error_msg = '';

	if ($_GET['page'] == 'install') {
		$page = 'default';

		$id = $db->escapeSimple($_GET['addon']);
		$addon = $digium_addons->get_addon($id);

		$digium_addons->install($id);
	} else if ($_GET['page'] == 'update') {
		$page = 'default';

		$id = $db->escapeSimple($_GET['addon']);
		$addon = $digium_addons->get_addon($id);

		$digium_addons->update($id);
	} else if ($_GET['page'] == 'uninstall') {
		$page = 'default';

		$id = $db->escapeSimple($_GET['addon']);
		$addon = $digium_addons->get_addon($id);

		$digium_addons->uninstall($id);
	} else if (isset($_POST['add_license_submit']) && $_POST['add_license_submit']) {
		$page = 'eula-form';

		$id = $db->escapeSimple($_GET['addon']);
		$addon = $digium_addons->get_addon($id);
		$digium_addons->register_load_product($addon['product_index']);
		$product = $digium_addons->register_get_product();
		$prefix = $digium_addons->register_get_key_prefix();

		$product_key = $db->escapeSimple($_POST['add_license_key']);
		if ( !$product_key || (strpos($product_key, $prefix) !== 0)) {
			$key_error_msg = "Invalid key.";
			$page = "add-license-form";
		}

		$submitted_ufs = array();
		foreach ($product['userfields'] as $uf) {
			if ($_POST['add_license_'.$uf['name']] == '' && $uf['required']) {
				$fields_error_msg = "Please enter values into the required fields.";
				$page = 'add-license-form';
			}

			$submitted_ufs[$uf['name']] = $db->escapeSimple($_POST['add_license_'.$uf['name']]);
		}
	} else if (isset($_POST['eula-submit']) && $_POST['eula-submit']) {
		$page = 'default';

		$id = $db->escapeSimple($_GET['addon']);
		$addon = $digium_addons->get_addon($id);
		$digium_addons->register_load_product($addon['product_index']);
		$product = $digium_addons->register_get_product();

		$product_key = $db->escapeSimple($_POST['add_license_key']);
		if ( !$product_key ) {
			$key_error_msg = "Invalid key.";
			$page = "add-license-form";
		}

		$submitted_ufs = array();
		foreach ($product['userfields'] as $uf) {
			if (isset($_POST['add_license_'.$uf['name']]) && $_POST['add_license_'.$uf['name']] == '' && $uf['required']) {
				$page = 'add-license-form';
			}

			if (isset($_POST['add_license_'.$uf['name']])) {
				$submitted_ufs[$uf['name']] = $db->escapeSimple($_POST['add_license_'.$uf['name']]);
			} else {
				$submitted_ufs[$uf['name']] = null;
			}
		}

		$register_result = $digium_addons->register($id, $submitted_ufs, $product_key);
		if ($register_result == false && $digium_addons->register_get_error() == 'bad-key') {
			$key_error_msg = "This is an invalid key.";
			$page = 'add-license-form';
		} else if ($register_result == false) {
			$error_msg = "There was an error attempting to register this product.";
			$page = 'eula-form';
		}
	} else if ($_GET['page'] == 'delete') {
		$id = $db->escapeSimple($_GET['addon']);
		$digium_addons->uninstall($id);
		$page='default';
	} else if ($_GET['page'] == 'backup') {
		$page='backup';
		$backup_link = $digium_addons->backup($_GET['addon']);
	}

	?>
	<style type="text/css">
		#digium_addons th, #digium_addons td { padding: 1px; text-align: center; }
		#digium_addons > tbody > tr:hover { background: #fde9d1; }
		#digium_addons th { background: #7aa8f9; }
		#digium_addons td { font-size: 12px; }

		.install_field { padding-bottom: 5px; }
		.error_msg { color: red }
		label { display: block; float: left; padding-right: 5px; text-align: right; width: 125px;}
		pre { font-size: 10px; }
	</style>

	<h1>Digium Addons</h1>
	<hr />

	<?php
	// Time to detemine what page to display
	switch ($page) {
		case 'add-license-form':
			$id = $db->escapeSimple($_GET['addon']);
			$addon = $digium_addons->get_addon($id);
			$digium_addons->register_load_product($addon['product_index']);
			$product = $digium_addons->register_get_product();
			include('modules/digiumaddoninstaller/views/add-license-form.php');
			break;
		case 'backup':
			include('modules/digiumaddoninstaller/views/backup.php');
			break;
		case 'eula-form':
			$id = $db->escapeSimple($_GET['addon']);
			$addon = $digium_addons->get_addon($id);
			$digium_addons->register_load_product($addon['product_index']);
			$product = $digium_addons->register_get_product();
			$eula = $digium_addons->register_get_eula($product_key);
			include('modules/digiumaddoninstaller/views/eula-form.php');
			break;
		case 'default':
		default:
			include('modules/digiumaddoninstaller/views/default.php');
	}	
} else {
	echo '<h1 style="color:red">This Module Requires The Digium RPM to be installed (php-digium_register-3.0.5-1_centos6.i686.rpm)</h1><br/>Please see this page for more information: <a target="_blank" href="http://wiki.freepbx.org/display/F2/Digium+Addons">http://wiki.freepbx.org/display/F2/Digium+Addons</a>';
}