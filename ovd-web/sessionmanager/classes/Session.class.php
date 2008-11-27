<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Session {
	public $id = NULL;
	public $session = NULL;
	public $server = NULL;
	public $folder = NULL;
	public $settings = array();

	public function __construct($session_, $server_='*') {
		Logger::debug('main', 'Starting SESSION::__construct for session '.$session_);

		$this->session = $session_;
		$this->id = $this->session;
		$this->server = $server_;

		if ($server_ == '*') {
			$session_folders = glob(SESSIONS_DIR.'/'.$server_.'/'.$session_);

			foreach ($session_folders as $session_folder) {
				$buf = explode('/', $session_folder);
				array_pop($buf);
				$this->server = array_pop($buf);
			}
		}

		$this->folder = SESSIONS_DIR.'/'.$this->server.'/'.$this->session;

		/*
		$prefs = Preferences::getInstance();
		if (! $prefs)
			return false;

		$fqdn_private_address = $prefs->get('general', 'fqdn_private_address');
		if (is_array($fqdn_private_address))
			if (isset($fqdn_private_address[$this->server]))
				$this->server = $fqdn_private_address[$this->server];
		*/

		if (file_exists($this->folder.'/settings'))
			$this->settings = unserialize(@file_get_contents($this->folder.'/settings'));
	}

	public function is_valid() {
		if (strpos($this->folder, '*') !== false)
			return false;

		return true;
	}

	public function add_session($die_=true) {
		Logger::debug('main', 'Starting SESSION::add_session for session '.$this->session.' on server '.$this->server);

		$server = new Server($this->server);
		if ($server->hasAttribute('locked')) {
			Logger::error('main', 'Server does not accept new sessions : '.$this->server);
			if ($die_ == true)
				die('Server does not accept new sessions : '.$this->server);
			return false;
		}
		if (!$server->hasAttribute('status') || $server->getAttribute('status') != 'ready') {
			Logger::error('main', 'Server does not accept new sessions : '.$this->server);
			if ($die_ == true)
				die('Server does not accept new sessions : '.$this->server);
			return false;
		}
		if ($server->getNbAvailableSessions() == 0) {
			Logger::error('main', 'Server does not accept new sessions : '.$this->server);
			if ($die_ == true)
				die('Server does not accept new sessions : '.$this->server);
			return false;
		}

		if (file_exists($this->folder)) {
			Logger::error('main', 'Session already exists : '.$this->folder);
			if ($die_ == true)
				die('Session already exists : '.$this->folder);
			else
				return false;
		}

		if (!file_exists(SESSIONS_DIR.'/'.$this->server)) {
			Logger::info('main', 'Server folder does not exist : '.SESSIONS_DIR.'/'.$this->server);

			if (! @mkdir(SESSIONS_DIR.'/'.$this->server, 0750)) {
				Logger::error('main', 'Server folder NOT created : '.SESSIONS_DIR.'/'.$this->server);
				if ($die_ == true)
					die('Server folder NOT created : '.SESSIONS_DIR.'/'.$this->server);
				else
					return false;
			}
			Logger::info('main', 'Server folder created : '.SESSIONS_DIR.'/'.$this->server);
		}

		if (! @mkdir($this->folder, 0750)) {
			Logger::error('main', 'Session folder NOT created : '.$this->folder);
			if ($die_ == true)
				die('Session folder NOT created : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Session folder created : '.$this->folder);

		if (! @touch($this->folder.'/available')){
			Logger::error('main', 'Session "available" file NOT created : '.$this->folder.'/available');
			if ($die_ == true)
				die('Session "available" file NOT created : '.$this->folder.'/available');
			else
				return false;
		}
		Logger::info('main', 'Session "available" file created : '.$this->folder.'/available');

		return true;
	}

	public function remove_session($die_=true) {
		Logger::debug('main', 'Starting SESSION::remove_session for session '.$this->session.' on server '.$this->server);

		if (!file_exists($this->folder)) {
			Logger::error('main', 'Session does not exist : '.$this->folder);
			if ($die_ == true)
				die('Session does not exist : '.$this->folder);
			else
				return false;
		}

		$remove_files = glob($this->folder.'/*');
		foreach ($remove_files as $remove_file)
			@unlink($remove_file);
		unset($remove_files);

		if (! @rmdir($this->folder)) {
			Logger::error('main', 'Session folder NOT removed : '.$this->folder);
			if ($die_ == true)
				die('Session folder NOT removed : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Session folder removed : '.$this->folder);

		if (count(glob(SESSIONS_DIR.'/'.$this->server.'/*')) == 0) {
			Logger::info('main', 'Server folder is empty : '.SESSIONS_DIR.'/'.$this->server);

			if (! @rmdir(SESSIONS_DIR.'/'.$this->server)) {
				Logger::error('main', 'Server folder NOT removed : '.SESSIONS_DIR.'/'.$this->server);
				if ($die_ == true)
					die('Server folder NOT removed : '.SESSIONS_DIR.'/'.$this->server);
				else
					return false;
			}
			Logger::info('main', 'Server folder removed : '.SESSIONS_DIR.'/'.$this->server);
		}

		return true;
	}

	public function use_session($die_=true) {
		Logger::debug('main', 'Starting SESSION::use_session for session '.$this->session.' on server '.$this->server);

		if (!file_exists($this->folder)) {
			Logger::error('main', 'Session does not exist : '.$this->folder);
			if ($die_ == true)
				die('Session does not exist : '.$this->folder);
			else
				return false;
		}

		$this->switch_session('available', 'used');

		return true;
	}

	public function switch_session($status_from_, $status_to_, $die_=true) {
		Logger::debug('main', 'Starting SESSION::switch_session for session '.$this->session.' on server '.$this->server.' : from '.$status_from_.' to '.$status_to_);

		if (! @unlink($this->folder.'/'.$status_from_)) {
			Logger::error('main', 'Session "'.$status_from_.'" file NOT removed : '.$this->folder.'/'.$status_from_);
			if ($die_ == true)
				die('Session "'.$status_from_.'" file NOT removed : '.$this->folder.'/'.$status_from_);
			else
				return false;
		}
		Logger::info('main', 'Session "'.$status_from_.'" file removed : '.$this->folder.'/'.$status_from_);

		if (! @touch($this->folder.'/'.$status_to_)) {
			Logger::error('main', 'Session "'.$status_to_.'" file NOT created : '.$this->folder.'/'.$status_to_);
			if ($die_ == true)
				die('Session "'.$status_to_.'" file NOT created : '.$this->folder.'/'.$status_to_);
			else
				return false;
		}
		Logger::info('main', 'Session "'.$status_to_.'" file created : '.$this->folder.'/'.$status_to_);

		return true;
	}

	public function session_exists() {
		$ret = $this->session_status();

		if ($ret != 3 && $ret != false) {
			if ($ret != 0)
				return 2; // session used
			elseif ($ret == 0)
				return 1; // session available
		}

		$this->remove_session(0);

		return false;
	}

	public function session_alive() {
		$ret = $this->session_status();

		if ($ret != 3 && $ret != false) {
			if ($ret == 2)
				return true; // session alive
		}

		return false;
	}

	public function session_status() {
		Logger::debug('main', 'Starting SESSION::session_status for session '.$this->session.' on server '.$this->server);

		$string = query_url('http://'.$this->server.'/webservices/session_status.php?session='.$this->session);

		if (!isset($string) || $string == '')
			$string = query_url('http://'.$this->server.'/webservices/session_status.php?session='.$this->session);

		if (is_numeric($string))
			return $string;

		return false;
	}

	public function create_token($mode_, $content_=array()) {
		Logger::debug('main', 'Starting SESSION::create_token for session '.$this->session.' on server '.$this->server);

		$token = md5(rand().time().rand());

		if (!file_exists(TOKENS_DIR.'/'.$token)) {
			if (@file_put_contents(TOKENS_DIR.'/'.$token, $mode_.':'.$this->session)) {
				Logger::info('main', 'Token created : '.TOKENS_DIR.'/'.$token);

				if ($mode_ == 'start') {
					$data = serialize($content_);

					if (@file_put_contents($this->folder.'/settings', $data))
						Logger::info('main', 'Session "start" token created : '.$this->folder.'/settings');
					else
						Logger::error('main', 'Session "start" token NOT created : '.$this->folder.'/settings');
				} elseif ($mode_ == 'invite') {
					$data = serialize($content_);

					if (@file_put_contents($this->folder.'/'.$token, $data))
						Logger::info('main', 'Session "invite" token created : '.$this->folder.'/'.$token);
					else
						Logger::error('main', 'Session "invite" token NOT created : '.$this->folder.'/'.$token);
				}

				return $token;
			} else
				Logger::error('main', 'Token NOT created : '.TOKENS_DIR.'/'.$token);
		} else {
			Logger::error('main', 'Token already exists : '.TOKENS_DIR.'/'.$token);
			//$this->create_token($mode_, $data_);
		}

		return false;
	}

	public function use_token($token_) {
		Logger::debug('main', 'Starting SESSION::use_token for token '.$token_);

		if (! @unlink(TOKENS_DIR.'/'.$token_)) {
			Logger::error('main', 'Unable to remove token : '.TOKENS_DIR.'/'.$token_);
			return false;
		}
		Logger::info('main', 'Token removed : '.TOKENS_DIR.'/'.$token_);

		return true;
	}

	public function getSetting($setting_) {
		Logger::debug('main', 'Starting SESSION::getSetting for setting '.$setting_);

		if (! isset($this->settings[$setting_])) {
			Logger::error('main', 'Unable to get setting : '.$setting_);
			return false;
		}
		Logger::info('main', 'Got setting : '.$setting_);

		return $this->settings[$setting_];
	}

	public function getStartTime() {
		Logger::debug('main', 'Starting SESSION::getStartTime for session '.$this->session);

		if (! file_exists($this->folder.'/used')) {
			Logger::error('main', 'Unable to get start time');
			return _('Unknown');
		}
		Logger::info('main', 'Got start time');

		return date('d/m/Y H:i:s', filemtime($this->folder.'/used'));
	}

	public function addInvite($email_) {
		Logger::debug('main', 'Starting SESSION::addInvite for session '.$this->session.' on server '.$this->server);

		if (@file_put_contents($this->folder.'/invited', $email_."\n", FILE_APPEND)) {
			Logger::info('main', 'Session invited file created : '.$this->folder.'/invited');

			return true;
		}
		Logger::error('main', 'Session invited file NOT created : '.$this->folder.'/invited');

		return false;
	}

	public function invitedEmails() {
		Logger::debug('main', 'Starting SESSION::invitedEmails for session '.$this->session.' on server '.$this->server);

		if (!file_exists($this->folder.'/invited'))
			return false;

		$buf = @file_get_contents($this->folder.'/invited');

		if (! $buf)
			return false;

		$ret = explode("\n", $buf);

		foreach ($ret as $k => $v)
			if ($v === '')
				unset($ret[$k]);

		return $ret;
	}

	/*public function add_sharing($magic_, $data_) {
		Logger::debug('main', 'Starting SESSION::add_sharing for magic '.$magic_);

		if (file_exists(SHARING_DIR.'/'.$magic_))
			Logger::warning('main', 'Sharing already exists : '.SHARING_DIR.'/'.$magic_);
		//else {
			if (! @file_put_contents(SHARING_DIR.'/'.$magic_, $data_))
				Logger::error('main', 'Sharing NOT created : '.SHARING_DIR.'/'.$magic_);
			else
				Logger::info('main', 'Sharing created : '.SHARING_DIR.'/'.$magic_);
		//}
	}

	public function remove_sharing($magic_) {
		Logger::debug('main', 'Starting SESSION::remove_sharing for magic '.$magic_);

		if (!file_exists(SHARING_DIR.'/'.$magic_))
			Logger::warning('main', 'Sharing does not exist : '.SHARING_DIR.'/'.$magic_);
		else {
			@unlink(SHARING_DIR.'/'.$magic_);

			if (!file_exists(SHARING_DIR.'/'.$magic_))
				Logger::info('main', 'Sharing removed : '.SHARING_DIR.'/'.$magic_);
			else
				Logger::error('main', 'Sharing NOT removed : '.SHARING_DIR.'/'.$magic_);
		}
	}

	public function use_sharing($magic_, $delete_=0) {
		Logger::debug('main', 'Starting SESSION::use_sharing for magic '.$magic_);

		if (!is_readable(SHARING_DIR.'/'.$magic_)) {
			Logger::error('main', 'Sharing file does not exist : '.SHARING_DIR.'/'.$magic_);
			die('Sharing file does not exist : '.SHARING_DIR.'/'.$magic_);
		}
		Logger::info('main', 'Sharing file found : '.SHARING_DIR.'/'.$magic_);

		$buf = trim(@file_get_contents(SHARING_DIR.'/'.$magic_));

		if (isset($buf) && $buf != '') {
			Logger::info('main', 'Sharing infos found for magic '.$magic_);

			if ($delete_ == 1)
				remove_sharing($magic_);

			return $buf;
		}
		Logger::error('main', 'Sharing infos NOT found for magic '.$magic_);

		return false;
	}*/
}
