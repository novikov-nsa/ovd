<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author David LECHEVALIER <david@ulteo.com> 2012
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

abstract class ProfileDB extends Module  {
	protected static $instance=NULL;
	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed', __FILE__, __LINE__);
			
			$mods_enable = $prefs->get('general', 'module_enable');
			if (!in_array('ProfileDB', $mods_enable)){
				die_error(_('ProfileDB module must be enabled'), __FILE__, __LINE__);
			}
			$mod_app_name = 'ProfileDB_'.$prefs->get('ProfileDB', 'enable');
			self::$instance = new $mod_app_name();
		}
		return self::$instance;
	}
	
	public function exists($id_) {
		return NULL;
	}
	
	public function isInternal() {
		return false;
	}
	
	public function import($id_) {
		return false;
	}
	
	public function importFromUser($id_) {
		return false;
	}
	
	public function importFromServer($server_fdqn_) {
		return false;
	}
	
	public function getList($order_=false) {
		return false;
	}
	
	public function count() {
		return false;
	}
	
	public function countOnServer($id_) {
		return false;
	}
	
	public function remove($profile_id_) {
		return false;
	}
	
	public function invalidate($profile_id_) {
		return false;
	}
	
	public function addToServer($profile_, $a_server) {
		return false;
	}
// 	
// 	public function deleteFromServer($sharedfolder_, $a_server) {
// 		return false;
// 	}
	
	public static function update($profile_) {
		return false;
	}

	public function addUserToProfile($usergroup_, $profile_) {
		return false;
	}
	
	public function delUserOfProfile($usergroup_, $profile_) {
		return false;
	}
	
	public function chooseFileServer() {
		return false;
	}
}
 
