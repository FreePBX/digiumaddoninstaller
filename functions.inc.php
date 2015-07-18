<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//  Copyright (c) 2009, Digium, Inc.
//

// Try to load our extension if it's not already loaded.
if(!extension_loaded('digium_register')) {
	return;
}

if (extension_loaded('digium_register')) {
	require_once(dirname(__FILE__).'/libdregister/digium_register.php');

	global $db;

	/**
	 * Digium Addon Installer Conf
	 *
	 * The conf class used by FreePBX to configure /etc/asterisk/ config files.
	 */
	class digiumaddoninstaller_conf {

		/**
		 * Get Filename
		 *
		 * No current files to be configured
		 */
		public function get_filename() {
			return '';
		}

		/**
		 * Generate Conf
		 *
		 * No current files to be generated
		 */
		public function generateConf($file) {
			return '';
		}
	}

	/**
	 * Digium Addons
	 *
	 * This class is used to manage addon information, (un)install addons, and register
	 * addons.
	 */
	class digium_addons {
		private $addons = array();		// The main addons array
		private $ast_version = '';		// The version of Asterisk
		private $bit = '';			// The server's bit
		private $downloads_addons_url = 'http://downloads.digium.com/pub/telephony/addons.json';
		private $hasinited = false;		// Has the module been initialized
		private $hasyum = true;			// Do we have yum installed?
		private $hasyumaccess = true;		// Do we have access to yum?
		private $module_version = '0.1';	// Version of the Digium Addons Module
		private $register = null;		// The digiumaddons_register object

		/**
		 * Constructor
		 *
		 * Load all needed information to use this class
		 */
		public function digium_addons() {
			$this->register = new digiumaddons_register();
			$this->get_ast_version();
			$this->load_addons();
			$this->check_for_updates();
		}

		/**
		 * Add Addon
		 *
		 * Add an addon to the database
		 */
		public function add_addon($name, $data) {
			global $db;

			$sql = array();

			$sql[] = sprintf("INSERT INTO digiumaddoninstaller_addons (id, name, description, documentation, link, product_index, category_index, register_limit, supported_version, is_installed, is_registered) VALUES (\"%s\", \"%s\", \"%s\", \"%s\", \"%s\", %d, %d, %d, \"%s\", false, false)",
				$db->escapeSimple($name),
				$db->escapeSimple($data['name']),
				$db->escapeSimple($data['description']),
				$db->escapeSimple($data['documentation']),
				$db->escapeSimple($data['link']),
				$db->escapeSimple($data['product_index']),
				$db->escapeSimple($data['category_index']),
				$db->escapeSimple($data['register_limit']),
				$db->escapeSimple($data['supported_version'])
			);

			foreach ($data['downloads'] as $dl) {
				$testsql = sprintf("SELECT id FROM digiumaddoninstaller_downloads WHERE id=\"%s\";", $db->escapeSimple($dl['name']));
				$result = $db->getAll($testsql);
				if (DB::IsError($result)) {
					die_freepbx($result->getDebugInfo());
				}

				if (sizeof($result) < 1) {
					$sql[] = sprintf("INSERT INTO digiumaddoninstaller_downloads (id, name, package, tarball, path, available_version) VALUES (\"%s\", \"%s\", \"%s\", \"%s\", \"%s\", \"%s\")",
					$db->escapeSimple($dl['name']),
					$db->escapeSimple($dl['name']),
					$db->escapeSimple($dl['package']),
					$db->escapeSimple($dl['tarball']),
					$db->escapeSimple($dl['path']),
					$db->escapeSimple($dl['version'])
					);
				}

				$sql[] = sprintf("INSERT INTO digiumaddoninstaller_addons_downloads (addon_id, download_id) VALUES (\"%s\", \"%s\")",
					$db->escapeSimple($name),
					$db->escapeSimple($dl['name'])
				);

				foreach ($dl['bits'] as $bit) {
					$sql[] = sprintf("INSERT INTO digiumaddoninstaller_downloads_bits (download_id, bit) VALUES (\"%s\", \"%s\")",
						$db->escapeSimple($dl['name']),
						$db->escapeSimple($bit)
					);
				}

				foreach ($dl['ast_versions'] as $ast_ver) {
					$sql[] = sprintf("INSERT INTO digiumaddoninstaller_downloads_ast_versions (download_id, ast_version) VALUES (\"%s\", \"%s\")",
						$db->escapeSimple($dl['name']),
						$db->escapeSimple($ast_ver)
					);
				}
			}

			foreach ($sql as $s) {
				$result = $db->query($s);
				if (DB::IsError($result)) {
					die_freepbx($result->getDebugInfo());
				}
				unset($result);
			}

			$add = array(
				'id' => $name,
				'name' => $data['name'],
				'description' => $data['description'],
				'downloads' => $data['downloads'],
				'link' => $data['link'],
				'status' => 'not_installed',
				'product_index' => $data['product_index'],
				'category_index' => $data['category_index'],
				'register_limit' => $data['register_limit'],
				'supported_version' => $data['supported_version'],
				'is_installed' => false,
				'is_registered' => false
			);
			$this->addons[$name] = $add;
		}

		/**
		 * Addon Exists
		 *
		 * Determine if the addon is already in the database
		 */
		public function addon_exists($name) {
			if ( ! isset($this->addons[$name])) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Backup
		 *
		 * Create a csv of all the licenses and their info so that
		 * one day it could be put back into
		 */
		public function backup($addon) {
			global $db;

			//get all addon registers from db
			$sql = sprintf("SELECT * FROM digiumaddoninstaller_registers WHERE addon_id = \"%s\"", $db->escapeSimple($addon));

			$result = $db->getAll($sql, DB_FETCHMODE_ASSOC);
			if (DB::IsError($result)) {
				die_freepbx($result->getDebugInfo());
				return false;
			}

			`touch /tmp/{$addon}-backup.csv`;

			$files = array();
			foreach ($result as $register) {
				//cp all license files into a tmp dir inside local dir
				$files[$register['filename']] = "{$register['path']}/{$register['filename']}";

				//make tmp csv file in tmp dir of all the db registers
				$register['data'] = str_replace("\n", "%newline%", $register['data']);
				$line = implode(", ", $register);
				`echo '{$line}' >> /tmp/{$addon}-backup.csv`;
			}
			$files = implode(" ", $files);

			//tar tmp dir
			`tar zcfP {$addon}-backup.tar.gz /tmp/{$addon}-backup.csv {$files}`;

			`rm -rf /tmp/{$addon}-backup.csv`;

			//return link
			return "/admin/{$addon}-backup.tar.gz";
		}

		/**
		 * Build Package Name
		 *
		 * Take a download and build what would be the yum package
		 */
		public function build_pkg_name($dl) {
			$pkg_format = $dl['package'];
			$name = $dl['name'];

			$ast = 'asterisk';

			$pkg_name = str_replace('{ast}', $ast, $pkg_format);
			$pkg_name = str_replace('{name}', $name, $pkg_name);

			return $pkg_name;
		}

		/**
		 * Check For Updates
		 *
		 * Pull the latest from the Digium server and check for any addon updates
		 */
		public function check_for_updates($addon=null) {
			global $db;

			if ($addon==null) {
				$tobechecked = $this->pull_addons_list();
			} else {
				$tobechecked = (is_array($addon)) ? $addon : array($addon);
			}

			foreach ($tobechecked as $name=>$add) {
				//pull info from downloads server
				$addon = $this->pull_addon($add);

				//check if addon exists in db, add if not
				if ( ! $this->addon_exists($name)) {
					$this->add_addon($name, $addon);
					continue;
				}

				$db_add = $this->addons[$name];
				//check if the addon's downloads have any updates
				foreach ($addon['downloads'] as $dl) {
					//get current version from database
					$sql = sprintf("SELECT available_version, installed_version FROM digiumaddoninstaller_downloads WHERE `id`=\"%s\" LIMIT 1",
						$db->escapeSimple($dl->name)
					);

					$result = $db->getAll($sql, DB_FETCHMODE_ASSOC);
					if (DB::IsError($result)) {
						die_freepbx($result->getDebugInfo());
						return false;
					}

					$curr_avail_ver = (isset($result['available_version'])) ? $result['available_version'] : null;
					$curr_install_ver = (isset($result['installed_version'])) ? $result['installed_version'] : null;

					//compare current version to pulled version
					if ($curr_avail_ver && (version_compare($curr_avail_ver, $dl->version) == -1)) {
						//update vars for dl
						$sql = array();

						$sql[] = sprintf("UPDATE digiumaddoninstaller_addons SET is_uptodate=false WHERE id=\"%s\"", $db->escapeSimple($addon['name']));

						$sql[] = sprintf("UPDATE digiumaddoninstaller_downloads SET package=\"%s\", tarball=\"%s\", path=\"%s\", available_version=\"%s\" WHERE name=\"%s\" LIMIT 1",
							$db->escapeSimple($dl->package),
							$db->escapeSimple($dl->tarball),
							$db->escapeSimple($dl->path),
							$db->escapeSimple($dl->version),
							$db->escapeSimple($dl->name)
						);

						$sql[] = "DELETE FROM digiumaddoninstaller_downloads_bits WHERE download_id=\"".$db->escapeSimple($dl->name)."\"";
						foreach ($dl->bits as $bit) {
							$sql[] = sprintf("INSERT INTO digiumaddoninstaller_downloads_bits (download_id, bit) VALUES (\"%s\", \"%s\")",
								$db->escapeSimple($dl->name),
								$db->escapeSimple($bit)
							);
						}

						$sql[] = "DELETE FROM digiumaddoninstaller_downloads_ast_versions WHERE download_id=\"".$db->escapeSimple($dl->name)."\"";
						foreach ($dl->ast_versions as $astver) {
							$sql[] = sprintf("INSERT INTO digiumaddoninstaller_downloads_ast_versions (download_id, ast_version) VALUES (\"%s\", \"%s\")",
								$db->escapeSimple($dl->name),
								$db->escapeSimple($astver)
							);
						}

						foreach ($sql as $s) {
							$result = $db->query($s);
							if (DB::IsError($result)) {
								die_freepbx($result->getDebugInfo());
								return false;
							}
						}

						$this->load_addons();
					}
				}
			}
		}

		/**
		 * Get Asterisk Version
		 *
		 * Get the version of Asterisk
		 */
		public function get_ast_version() {
			$full = `asterisk -V`;
			if (preg_match("/1\.[2468](\.[0-9][0-9]?(\.[0-9][0-9]?)?)?/", $full, $matches)) {
				$this->ast_version = $matches[0];
			} else if (strpos('branch', $full)) {
				$this->ast_version = "team-branch";	// most likely at least.
			} else {
				$this->ast_version = '';
			}
			return $this->ast_version;	// something like "1.6.1.5"
		}

		/**
		 *
		 */
		public function get_addon($id) {
			if ( ! isset($this->addons[$id])) {
				return false;
			}

			return $this->addons[$id];
		}

		/**
		 * Get Addons
		 *
		 * Get an array of the available addons
		 */
		public function get_addons() {
			return $this->addons;
		}

		/**
		 * Install
		 *
		 * Install the addon selected
		 */
		public function install($id) {
			global $db;

			$addon = $this->addons[$id];

			foreach ($addon['downloads'] as $dl) {
				if ($dl['available_version'] == $dl['installed_version']) {
					continue;
				} else if ($this->hasyum && $this->hasyumaccess) {
					$pkg_name = $this->build_pkg_name($dl);

					$retval = `sudo yum install -y $pkg_name`;

					$sql = sprintf("UPDATE digiumaddoninstaller_downloads SET installed_version=\"%s\" WHERE id=\"%s\"", $db->escapeSimple($dl['available_version']), $db->escapeSimple($dl['name']));

					$results = $db->query($sql);
					if (DB::IsError($results)) {
						die_freepbx($results->getDebugInfo());
						return false;
					}

					$dl['installed_version'] = $dl['available_version'];
				} else {
					//not yet implemented
				}
			}

			$sql = sprintf("UPDATE digiumaddoninstaller_addons SET is_installed=true, is_uptodate=true WHERE id=\"%s\"", $db->escapeSimple($id));

			$results = $db->query($sql);
			if (DB::IsError($results)) {
				die_freepbx($results->getDebugInfo());
				return false;
			}

			$this->addons[$id]['is_uptodate'] = true;
			$this->addons[$id]['is_installed'] = true;
		}

		/**
		 * Load Addons
		 *
		 * Get addon information from the database
		 */
		public function load_addons() {
			global $db;

			unset($this->addons);
			$this->addons = array();

			$sql = "SELECT * FROM digiumaddoninstaller_addons";

			$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
			if (DB::IsError($results)) {
				die_freepbx($results->getDebugInfo());
				return false;
			}

			foreach ($results as $row) {
				$this->addons[$row['id']] = $row;

				$sql = sprintf("SELECT * FROM digiumaddoninstaller_downloads INNER JOIN digiumaddoninstaller_addons_downloads WHERE digiumaddoninstaller_addons_downloads.download_id = digiumaddoninstaller_downloads.id AND digiumaddoninstaller_addons_downloads.addon_id=\"%s\"", $db->escapeSimple($row['id']));

				$dls = $db->getAll($sql, DB_FETCHMODE_ASSOC);
				if (DB::IsError($dls)) {
					die_freepbx($dls->getDebugInfo());
					return false;
				}

				$this->addons[$row['id']]['downloads'] = array();
				foreach ($dls as $dl) {
					$sql = sprintf("SELECT * FROM digiumaddoninstaller_downloads_bits WHERE download_id=\"%s\"", $db->escapeSimple($dl['id']));

					$bits = $db->getAll($sql, DB_FETCHMODE_ASSOC);
					if (DB::IsError($bits)) {
						die_freepbx($bits->getDebugInfo());
						return false;
					}

					$dl['bits'] = array();
					foreach ($bits as $bit) {
						$dl['bits'][] = $bit;
					}

					$sql = sprintf("SELECT * FROM digiumaddoninstaller_downloads_ast_versions WHERE download_id=\"%s\"", $db->escapeSimple($dl['id']));

					$astvers = $db->getAll($sql, DB_FETCHMODE_ASSOC);
					if (DB::IsError($astvers)) {
						die_freepbx($astvers->getDebugInfo());
						return false;
					}

					$dl['ast_versions'] = array();
					foreach ($astvers as $astver) {
						$dl['ast_versions'][] = $astver;
					}

					$this->addons[$row['id']]['downloads'][] = $dl;
				}

				$this->addons[$row['id']]['registers'] = array();

				$sql = sprintf("SELECT * FROM digiumaddoninstaller_registers WHERE addon_id=\"%s\"", $db->escapeSimple($row['id']));

				$regs = $db->getAll($sql, DB_FETCHMODE_ASSOC);
				if (DB::IsError($regs)) {
					die_freepbx($dls->getDebugInfo());
					return false;
				}

				foreach ($regs as $reg) {
					$this->addons[$row['id']]['registers'][] = $reg;
				}
			}
		}

		/**
		 * Pull Addon
		 *
		 * Pull addon info from the Digium downloads server
		 */
		public function pull_addon($url) {
			$request = file_get_contents($url);
			$request = str_replace(array("\n", "\t"), "", $request);
			return json_decode($request, true);
		}

		/**
		 * Pull Addons List
		 *
		 * Pull the list of available addons from the Digium downloads server
		 */
		public function pull_addons_list() {
			$request = file_get_contents($this->downloads_addons_url);
			$request = str_replace(array("\n", "\t"), "", $request);
			return json_decode($request, true);
		}

		public function register($id, $ufs, $key) {
			global $db;

			$retval = $this->register_register($ufs, $key, $id);
			if (!$retval) {
				return $retval;
			}

			$sql = sprintf("UPDATE digiumaddoninstaller_addons SET is_registered=true WHERE id=\"%s\"", $db->escapeSimple($id));

			$result = $db->query($sql);
			if (DB::IsError($result)) {
				die_freepbx($result->getDebugInfo());
				return false;
			}
			needreload();

			return true;
		}

		public function register_check_key($key) {
			return $this->register->check_key($key);
		}

		public function register_get_eula($key) {
			return $this->register->get_eula($key);
		}

		public function register_get_error() {
			return $this->register->get_error();
		}

		public function register_get_key_prefix() {
			return $this->register->get_key_prefix();
		}

		public function register_get_product() {
			return $this->register->get_product();
		}

		public function register_load_product($index) {
			return $this->register->load_product($index);
		}

		private function register_register($userfields, $key, $id) {
			return $this->register->register($userfields, $key, $id);
		}

		public function uninstall($id) {
			global $db;

			$addon = $this->addons[$id];

			foreach ($addon['downloads'] as $dl) {
				if ($dl['installed_version'] == '') {
					continue;
				} else if ($this->hasyum && $this->hasyumaccess) {
					$pkg_name = $this->build_pkg_name($dl);

					$retval = `sudo yum erase -y $pkg_name`;

					$sql = sprintf("UPDATE digiumaddoninstaller_downloads SET installed_version='' WHERE id=\"%s\"", $db->escapeSimple($dl['name']));

					$results = $db->query($sql);
					if (DB::IsError($results)) {
						die_freepbx($results->getDebugInfo());
						return false;
					}

					$dl['installed_version'] = '';
				} else {
					//not yet implemented
				}
			}

			$sql = sprintf("UPDATE digiumaddoninstaller_addons SET is_installed=false, is_uptodate=false WHERE id=\"%s\"", $db->escapeSimple($id));

			$results = $db->query($sql);
			if (DB::IsError($results)) {
				die_freepbx($results->getDebugInfo());
				return false;
			}

			$this->addons[$id]['is_uptodate'] = false;
			$this->addons[$id]['is_installed'] = false;
		}

		public function update($id) {
			global $db;

			$addon = $this->addons[$id];

			foreach ($addon['downloads'] as $dl) {
				if ($this->hasyum && $this->hasyumaccess) {
					$pkg_name = $this->build_pkg_name($dl);

					$retval = `sudo yum update -y $pkg_name`;

					$sql = sprintf("UPDATE digiumaddoninstaller_downloads SET installed_version=\"%s\" WHERE id=\"%s\"", $db->escapeSimple($dl['available_version']), $db->escapeSimple($dl['id']));

					$results = $db->query($sql);
					if (DB::IsError($results)) {
						die_freepbx($results->getDebugInfo());
						return false;
					}

					$dl['installed_version'] = $dl['available_version'];
				} else {
					//not yet implemented
				}
			}

			$sql = sprintf("UPDATE digiumaddoninstaller_addons SET is_uptodate=true WHERE id=\"%s\"", $db->escapeSimple($id));

			$results = $db->query($sql);
			if (DB::IsError($results)) {
				die_freepbx($results->getDebugInfo());
				return false;
			}

			$this->addons[$id]['is_uptodate'] = true;
		}
	}

	class digiumaddons_register {
		private $category = 0;			// The register category default is "Digium Products"
		private $cat_res = null;		// Category resource
		private $error = '';			// Error when registering
		private $key_prefix = null;		// The Prefix required for the license key
		private $license_res = null;		// An array of license resources
		private $licenses = array();		// An array of licenses' info
		private $product = array();		// Assoc array to store product info
		private $product_index = null;		// Product index must be selected by user
		private $product_key = null;		// The Product Key
		private $product_res = null;		// Product resource
		private $status = array();		// Status
		private $status_res = null;		// Status resource
		private $userfield_list_res = null;	// Userfield List resource

		public function __construct() {
			$r = dreg_get_product_categories();
			$this->cat_res = dreg_find_category_by_index($r, $this->category);
		}

		public function check_key($key=null) {
			if (!isset($key) && !isset($this->product_key)) {
				die_freepbx('Key is cannot be null when checking');
				return false;
			} else if ( ! isset($this->product)) {
				die_freepbx('Please load a product before attempting to check a key');
			}

			$this->product_key = $key;

			$this->status_res = new_status();
			status_check_key($this->status_res, $this->product['id'] ,$key);

			$status['code'] = status_code_get($this->status_res);
			$status['message'] = status_message_get($this->status_res);
		}

		public function get_eula($key=null) {
			if (!isset($key) && !isset($this->product_key)) {
				die_freepbx('Key is cannot be null when obtaining a eula');
				return false;
			} else if ( ! isset($this->product)) {
				die_freepbx('Please load a product before attempting to get a eula');
			} else if (isset($this->product['eula'])) {
				return $this->product['eula'];
			}

			$this->product_key = (isset($key)) ? $key : $this->product_key;

			$this->product['eula'] = dreg_get_eula($this->product_res, $this->product_key, "en");
			return $this->product['eula'];
		}

		public function get_error() {
			return $this->error;
		}

		public function get_key_prefix() {
			$this->key_prefix = dreg_product_key_prefix_get($this->product_res);
			return $this->key_prefix;
		}

		public function get_product() {
			if ( ! isset($this->product)) {
				die_freepbx('Please load the product before attempting to get it');
				return false;
			}

			return $this->product;
		}

		public function load_product($index) {
			if ( ! is_numeric($index)) {
				die_freepbx('Index not numeric when loading Digium Product');
				return false;
			}

			unset($this->product);
			unset($this->product_index);
			unset($this->product_key);
			unset($this->product_res);

			$this->product_index = $index;

			$pl_res = dreg_get_products($this->cat_res);
			$this->product_res = dreg_find_product_by_index($pl_res, $this->product_index);
			$this->product['id'] = dreg_product_id_get($this->product_res);
			$this->product['name'] = dreg_product_name_get($this->product_res);

			$this->product['userfields'] = array();
			$this->userfield_list_res = dreg_get_product_reg_requirements($this->product_res, "en");
			for (
			     $uf_res = dreg_userfield_list_first_get($this->userfield_list_res);
			     $uf_res;
			     $uf_res = dreg_userfield_entry_next_get(dreg_userfield_entry_get($uf_res))
			) {
				$uf = array();
				$uf['name'] = dreg_userfield_field_name_get($uf_res);
				$uf['desc'] = dreg_userfield_desc_get($uf_res);
				$uf['required'] = dreg_userfield_required_get($uf_res);

				$this->product['userfields'][] = $uf;
			}
		}

		public function register($ufs, $key=null, $addon) {
			global $db;

			if ($this->product_res == null && $this->product_index != null) {
				$this->load_product($this->product_index);
			} else if ($key == null && $this->product_key == null) {
				die_freepbx('Please provide a key before attempting to register.');
				return false;
			} if ($key != null) {
				$this->product_key = $key;
			}

			if ($this->product_res == null) {
				die_freepbx('Please provide a product before attempting to register.');
				return false;
			}

			$this->userfield_list_res = dreg_get_product_reg_requirements($this->product_res, "en");
			for (
			     $uf_res = dreg_userfield_list_first_get($this->userfield_list_res);
			     $uf_res;
			     $uf_res = dreg_userfield_entry_next_get(dreg_userfield_entry_get($uf_res))
			) {
				$name = dreg_userfield_field_name_get($uf_res);

				dreg_userfield_data_set($uf_res, $ufs[$name]);
			}

			$userfield_obj = new digiumaddons_userfield_list($this->userfield_list_res);

			$license_list_res = dreg_register_product($this->product_res, $this->userfield_list_res, dreg_get_hostid(), $this->product_key, "linux", 0);
			if (!isset($license_list_res) || $license_list_res  == '') {
				$this->error = 'bad-key';
				return false;
			}

			for (
			     $license_res = dreg_license_list_first_get($license_list_res);
			     $license_res;
			     $license_res = dreg_license_entry_next_get(dreg_license_entry_get($license_res))
			) {
				$this->licenses[$i] = array();
				$this->licenses[$i]['path'] = dreg_license_path_get($license_res);
				$this->licenses[$i]['filename'] = dreg_license_filename_get($license_res);
				$this->licenses[$i]['data'] = dreg_license_data_get($license_res);
				$this->licenses[$i]['status'] = dreg_license_status_get($license_res);

				$status_code = trim(status_code_get($this->licenses[$i]['status']));
				if ($status_code != '200' && $status_code != '210') {
					die_freepbx('Product Registration Error: '.trim(status_code_get($this->licenses[$i]['status'])));
					return false;
				}

				$fh = fopen($this->licenses[$i]['path'] . '/' . $this->licenses[$i]['filename'], 'w');
				if ( ! $fh) {
					die_freepbx('Failed to open file for license. Do you have the right permissions?');
					return false;
				}

				fwrite($fh, $this->licenses[$i]['data']);
				fclose($fh);
				unset($fh);

				$sql = sprintf("INSERT INTO digiumaddoninstaller_registers (addon_id, path, filename, data) VALUES (\"%s\", \"%s\", \"%s\", \"%s\")",
					$db->escapeSimple($addon),
					$db->escapeSimple($this->licenses[$i]['path']),
					$db->escapeSimple($this->licenses[$i]['filename']),
					$db->escapeSimple($this->licenses[$i]['data'])
				);

				$result = $db->query($sql);
				if (DB::IsError($result)) {
					die_freepbx($result->getDebugInfo());
					return false;
				}
			}

			return true;
		}
	}

	class digiumaddons_userfield_list {
		public $ptr = null;

		public function __construct($p) {
			$this->ptr = $p;
		}

		public function __get($var) {
			if ($var == 'first') return dreg_userfield_list_first_get($this->_cPtr);
			if ($var == 'last_elm') return dreg_userfield_list_last_elm_get($this->_cPtr);
			return null;
		}

		public function num_userfields() {
			return dreg_userfield_list_num_userfields($this->_cPtr);
		}

		public function get_userfield($index) {
			$r=dreg_userfield_list_userfield_get($this->_cPtr,$index);
			return is_resource($r) ? new dreg_userfield($r) : $r;
		}

	}

	if ( !function_exists('json_decode') ){
		function json_decode($content, $assoc=false){
			require_once 'Services/JSON.php';
			if ( $assoc ){
				$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
			} else {
				$json = new Services_JSON;
			}
			return $json->decode($content);
		}
	}

	if ( !function_exists('json_encode') ){
		function json_encode($content){
			require_once 'Services/JSON.php';
			$json = new Services_JSON;

			return $json->encode($content);
		}
	}
}
