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

global $db;
global $amp_conf;
global $asterisk_conf;

$sql = array();

$sql[] = "CREATE TABLE IF NOT EXISTS digiumaddoninstaller_system (
	`id` INT UNSIGNED PRIMARY KEY auto_increment,
	`variable` VARCHAR(50) NOT NULL UNIQUE DEFAULT '',
	`value` VARCHAR(255) NOT NULL DEFAULT ''
);";

$sql[] = "CREATE TABLE IF NOT EXISTS digiumaddoninstaller_addons (
	`id` VARCHAR(25) PRIMARY KEY,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`description` TEXT,
	`link` VARCHAR(255) NOT NULL DEFAULT '',
	`documentation` VARCHAR(255) NOT NULL DEFAULT '',
	`product_index` INT UNSIGNED NOT NULL,
	`category_index` INT UNSIGNED NOT NULL,
	`register_limit` INT UNSIGNED NOT NULL DEFAULT 0,
	`status` VARCHAR(50) NOT NULL DEFAULT 'not_installed',
	`supported_version` VARCHAR(50) DEFAULT '0.1',
	`is_installed` BOOLEAN NOT NULL DEFAULT false,
	`is_uptodate` BOOLEAN NOT NULL DEFAULT false,
	`is_registered` BOOLEAN NOT NULL DEFAULT false
);";

$sql[] = "CREATE TABLE IF NOT EXISTS digiumaddoninstaller_downloads (
	`id` VARCHAR(25) PRIMARY KEY,
	`name` VARCHAR(100) NOT NULL DEFAULT '',
	`package` VARCHAR(255) NOT NULL DEFAULT '{ast}-{name}',
	`tarball` VARCHAR(255) NOT NULL DEFAULT '{name}-{astver}_{version}-{arch}.tar.gz',
	`path` VARCHAR(255) NOT NULL DEFAULT '',
	`installed_version` VARCHAR(100) NOT NULL DEFAULT '',
	`available_version` VARCHAR(100) NOT NULL DEFAULT ''
);";

$sql[] = "CREATE TABLE IF NOT EXISTS digiumaddoninstaller_addons_downloads (
	`id` INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
	`addon_id` VARCHAR(25),
	`download_id` VARCHAR(25)
);";

$sql[] = "CREATE TABLE IF NOT EXISTS digiumaddoninstaller_downloads_bits (
	`id` INT UNSIGNED PRIMARY KEY auto_increment,
	`download_id` VARCHAR(25) NOT NULL,
	`bit` VARCHAR(10)
)";

$sql[] = "CREATE TABLE IF NOT EXISTS digiumaddoninstaller_downloads_ast_versions (
	`id` INT UNSIGNED PRIMARY KEY auto_increment,
	`download_id` VARCHAR(25) NOT NULL,
	`ast_version` VARCHAR(50) NOT NULL
)";

$sql[] = "CREATE TABLE IF NOT EXISTS digiumaddoninstaller_registers (
	`id` INT UNSIGNED PRIMARY KEY auto_increment,
	`addon_id` VARCHAR(25) NOT NULL,
	`path` VARCHAR(255) NOT NULL,
	`filename` VARCHAR(255) NOT NULL,
	`data` TEXT NOT NULL,
	`time_registered` TIMESTAMP NOT NULL DEFAULT current_timestamp
);";

foreach ($sql as $s) {
	$result = $db->query($s);
	if (DB::IsError($result)) {
		die_freepbx($result->getDebugInfo());
	}
	unset($result);
}

$sql = array();

$sql[] = "INSERT INTO digiumaddoninstaller_system (`variable`, `value`) VALUES ('bit', '32')";
$sql[] = "INSERT INTO digiumaddoninstaller_system (`variable`, `value`) VALUES ('hasinited', 'yes')";
$sql[] = "INSERT INTO digiumaddoninstaller_system (`variable`, `value`) VALUES ('hasyum', 'yes')";
$sql[] = "INSERT INTO digiumaddoninstaller_system (`variable`, `value`) VALUES ('hasyumaccess', 'yes')";
$sql[] = "INSERT INTO digiumaddoninstaller_system (`variable`, `value`) VALUES ('lastupdated', NOW())";

foreach ($sql as $s) {
	$result = $db->query($s);
	unset($result);
}

$sql = sprintf('UPDATE digiumaddoninstaller_system SET value="%s" WHERE variable="bit";', $bit);
$result = $db->query($sql);
if (DB::IsError($regs)) {
	die_freepbx($dls->getDebugInfo());
	return false;
}

//end of file
