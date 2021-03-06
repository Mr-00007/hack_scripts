<?php

class mysqli_db{
	public $settings, $sql, $sql_time, $db, $errors, $connect;
	
	function __construct(){
		$this->sql = array();
		$this->sql_time = array();
		$this->db = array();
		$this->errors = array();
		$this->connect = array();
		
		$this->settings = array();
		$this->settings["debug"] = false;
		$this->settings["timezone"] = '+03:00';
		$this->settings["save_sql"] = false;
		$this->settings["save_prefix"] = false;
		$this->settings["ping"] = false;
		
		@$this->settings['options'][MYSQLI_INIT_COMMAND] = array();
		@$this->settings['options'][MYSQLI_INIT_COMMAND][] = 'SET AUTOCOMMIT=0';
		@$this->settings['options'][MYSQLI_INIT_COMMAND][] = 'SET NAMES \'utf8\'';
		@$this->settings['options'][MYSQLI_INIT_COMMAND][] = 'SET TIME_ZONE = \''.$this->settings["timezone"].'\';';
		@$this->settings['options'][MYSQLI_INIT_COMMAND][] = 'SET SQL_BIG_SELECTS=1';
		
		@$this->settings['options'][MYSQLI_OPT_CONNECT_TIMEOUT] = 5;
	}

	function connect($host,$user,$password,$use_db,$socket = ''){
		$mysqli = mysqli_init();
		
		if(count($this->settings["options"]) > 0){
			foreach($this->settings["options"] as $key => $value){
				if(is_array($value)){
					if(count($value) > 0){
						foreach($value as $a_value){
							$mysqli->options($key, $a_value);
						}
					}
				}else{
					$mysqli->options($key, $value);
				}
			}
		}
		
		@$mysqli->real_connect($host, $user, $password, $use_db);
		
		if($mysqli->connect_error){
			$this->errors[] = $mysqli->connect_error;
			unset($mysqli);
		}else{
			$this->db[] = $mysqli;
			$this->connect[] = array('host' => $host, 'user' => $user, 'password' => $password, 'use_db' => $use_db, 'socket' => $socket);
			unset($mysqli);
			if(!isset($this->db['default'])) $this->db['default'] = &$this->db[count($this->db)-1];
		}
	}

	function disconnect($db = null){
		if(!isset($db)) $db = &$this->db['default'];
		$db->close();
		$db = false;

		foreach($this->db as $key => $value){
			if($this->db[$key] == false) unset($this->db[$key]);
		}
	}

	function query($sql, $db = null, $function = null, $check_one = true){
		if(!isset($db)) $db = &$this->db['default'];
		
		if($this->settings["debug"] == true) $this->sql[] = $sql; 
		
		if($this->settings["save_sql"] != false){
			if(!empty($this->settings["save_prefix"])){
				@file_put_contents($this->settings["save_sql"], $this->settings["save_prefix"] . ': ' . $sql . "\r\n", FILE_APPEND);
			}else{
				@file_put_contents($this->settings["save_sql"], $sql . "\r\n", FILE_APPEND);
			}
		}
		
		switch(1){
			case (stripos($sql, 'show') === 0):
			case (stripos($sql, 'select') === 0):
				if(null == ($result = $db->query($sql))) return null;
				
				switch(null){
					case !$function:
						while($row = $result->fetch_object()) call_user_func_array($function, array($row));
						$result->free_result();
					break;
					
					default:
						if($check_one == true){
							if($result->num_rows == 1){
								$return = $result->fetch_object();
							}else{
								while($row = $result->fetch_object()) $return[] = $row;
							}
						}else{
							while($row = $result->fetch_object()) $return[] = $row;
						}
						$result->free_result();
						if(isset($return)){return $return;}
					break;
				}
			break;
			
			case (stripos($sql, 'insert') === 0):
				$db->real_query($sql);
				$insert_id = $db->insert_id;
				return !empty($insert_id) ? $db->insert_id : false;
			break;
			
			default:
				return $db->real_query($sql);
			break;
		}
	}

	function multi_query($sql, $db = null){
		if(!isset($db)) $db = &$this->db['default'];
		if($this->settings["debug"] == true) $this->sql[] = $sql; 
		
		if($this->settings["save_sql"] != false){
			if(!empty($this->settings["save_prefix"])){
				@file_put_contents($this->settings["save_sql"], $this->settings["save_prefix"] . ': ' . $sql . "\r\n", FILE_APPEND);
			}else{
				@file_put_contents($this->settings["save_sql"], $sql . "\r\n", FILE_APPEND);
			}
		}
		return $db->multi_query($sql);
	}

	function query_cache($sql, $db = null, $time = 60, $array = false){
		$md5 = md5($sql);
		if(file_exists('cache/sqls/' . $md5 . '.json')){
			if(time() - filemtime('cache/sqls/' . $md5 . '.json') >= $time){
				if($array == true){
					$result = $this->query($sql, $db, null, false);
				}else{
					$result = $this->query($sql, $db);
				}
				file_put_contents('cache/sqls/' . $md5 . '.json', json_encode($result));
			}else{
				$result = json_decode(file_get_contents('cache/sqls/' . $md5 . '.json'), false);
			}
		}else{
			if($array == true){
				$result = $this->query($sql, $db, null, false);
			}else{
				$result = $this->query($sql, $db);
			}
			file_put_contents('cache/sqls/' . $md5 . '.json', json_encode($result));
		}
		return $result;
	}

	function query_name($sql, $db = null, $name_constant = 'count', $default_return = '0', $cache = false, $time = 60){
		if(!isset($db)) $db = &$this->db['default'];
		
		if(stripos($sql, 'select') === 0){
			if($cache != false){
				$row = $this->query_cache($sql, $db, $time);
			}else{
				$row = $this->query($sql);
			}
			
			if(is_array($name_constant)){
				$return = array();
				foreach($name_constant as $value){
					if(isset($row->$value)){
						$return[$value] = $row->$value;
					}else{
						$return[$value] = $default_return;
					}
				}
				return $return;
			}else{
				if(isset($row->$name_constant)){
					return $row->$name_constant;
				}else{
					return $default_return;
				}
			}

		}else{
			if($cache != false){
				$row = $this->query_cache($sql, $db, $time);
			}else{
				$row = $this->query($sql);
			}

			if(isset($row->$name_constant)){
				return $row->$name_constant;
			}else{
				return $default_return;
			}
		}
	}

	function query2array($sql, $db = null, $function = null, $name_to_key = ''){
		if(!isset($db)) $db = &$this->db['default'];

		if($this->settings["debug"] == true) $this->sql[] = $sql; 
		
		if($this->settings["save_sql"] != false){
			if(!empty($this->settings["save_prefix"])){
				@file_put_contents($this->settings["save_sql"], $this->settings["save_prefix"] . ': ' . $sql . "\r\n", FILE_APPEND);
			}else{
				@file_put_contents($this->settings["save_sql"], $sql . "\r\n", FILE_APPEND);
			}
		}
		
		switch(1){
			case (stripos($sql, 'show') === 0):
			case (stripos($sql, 'select') === 0):
				if(null == ($result = $db->query($sql))) return null;
				
				switch(null){
					case !$function:
						while($row = $result->fetch_array()) call_user_func_array($function, array($row));
						$result->free_result();
					break;
					
					default:
						if($result->num_rows == 1){
							$return = $result->fetch_array();
						}else{
							if(!empty($name_to_key)){
								while($row = $result->fetch_array()) $return[$row[$name_to_key]] = $row;
							}else{
								while($row = $result->fetch_array()) $return[] = $row;
							}
						}
						$result->free_result();
						if(isset($return)){return $return;}
					break;
				}
			break;
		}
	}

	function server_info($db = null){
		if(!isset($db)) $db = &$this->db['default'];
		return $db->server_info;
	}
	
	function table_rows($db_name, $db = null, $function = null) {
		$result = $this->query('SHOW TABLE STATUS WHERE (Name=\''.$db_name.'\')', $db, $function);
		return $result->Rows;
	}
	
	function table_check($db_name, $db = null, $function = null) {
		$result = $this->query('SHOW TABLE STATUS WHERE (Name=\''.$db_name.'\')', $db, $function);
		return $result;
	}
	
	function firstConnectorToDefault() {
		if(isset($this->db[0])){
			$this->db["default"] = &$this->db[0];
			return true;
		}else{
			return false;
		}
	}
	
	function lastConnectorToDefault() {
		if(isset($this->db[(count($this->db)-2)])){
			$this->db["default"] = &$this->db[(count($this->db)-2)];
			return true;
		}else{
			return false;
		}
	}
	
	function real_escape_string($value, $db = null){
		if(!isset($db)) $db = &$this->db['default'];
		return $db->real_escape_string($value);
	}
}
?>