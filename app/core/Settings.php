<?php

class Settings{

	public $db;

	public function __construct(){
		$this->db = array(
			'db_host' => '127.0.0.1',
			'db_name' => 'db',
			'db_user' => 'root',
			'db_passwd' => ''
		);
	}
}

?>
