<?php
class Settings_model extends CI_Model {

	public function __construct() {
		$this->load->database();
	}

	public function getAll() {
		$this->db->from('settings');
		
		$query = $this->db->get();
		return $query->result_array();
	}

	public function getdbTables() {
		return $this->db->list_tables();
	}
	
	public function updateSettings($sort, $update = array()) {
 
 		if (!empty($update) && !empty($sort)) {
			$this->db->where('sort', $sort);
			$this->db->delete('settings');

			foreach ($update as $key => $value) {
				if (is_array($value)) {
					$this->db->set('sort', $sort);
					$this->db->set('key', $key);
					$this->db->set('value', serialize($value));
					$this->db->set('serialized', '1');
					$this->db->insert('settings');
				} else {
					$this->db->set('sort', $sort);
					$this->db->set('key', $key);
					$this->db->set('value', $value);
					$this->db->set('serialized', '0');
					$this->db->insert('settings');
				}
			}
		
			return TRUE;
		}
 	}
	
	public function deleteSettings($sort) {
		$this->db->where('sort', $sort);
			
		return $this->db->delete('settings');
	}

	function getTimezones() {
		$timezoneIdentifiers = DateTimeZone::listIdentifiers();
		$utcTime = new DateTime('now', new DateTimeZone('UTC'));
 
		$tempTimezones = array();
		foreach ($timezoneIdentifiers as $timezoneIdentifier) {
			$currentTimezone = new DateTimeZone($timezoneIdentifier);
 
			$tempTimezones[] = array(
				'offset' => (int)$currentTimezone->getOffset($utcTime),
				'identifier' => $timezoneIdentifier
			);
		}
 
		// Sort the array by offset,identifier ascending
		usort($tempTimezones, function($a, $b) {
			return ($a['offset'] == $b['offset'])
				? strcmp($a['identifier'], $b['identifier'])
				: $a['offset'] - $b['offset'];
		});
 
		$timezoneList = array();
		foreach ($tempTimezones as $tz) {
			$sign = ($tz['offset'] > 0) ? '+' : '-';
			$offset = gmdate('H:i', abs($tz['offset']));
			$timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset .') '. $tz['identifier'];
		}
 
		return $timezoneList;
	}

	public function backupDatabase($tables = array()) {

		if (!empty($tables)) {
			$this->load->dbutil();
			$this->load->helper('file');

			$prefs = array(
				'tables'      => $tables,  // Array of tables to backup.
				'format'      => 'txt',             // gzip, zip, txt
				'add_drop'    => TRUE,              // Whether to add DROP TABLE statements to backup file
				'add_insert'  => TRUE,              // Whether to add INSERT data to backup file
				'newline'     => "\n"               // Newline character used in backup file
			);

			$backup =& $this->dbutil->backup($prefs);
		
			$name = 'tastyigniter.sql';

			$this->load->helper('download');
			force_download($name, $backup); 
			
			return TRUE;
		}
		
		return FALSE;
	}

	public function restoreDatabase($sql) {
		foreach (explode(";\n", $sql) as $sql) {
    		$sql = trim($sql);
    		
			if ($sql) {
      			$this->db->query($sql);
    		}
  		}
  		
  		return TRUE;
	}
}
