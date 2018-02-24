<?php

class Render{

	public $page;

	public function __construct($page){
		ob_start();
		require_once __DIR__.'/../views/'.$page.'.php';
		$this->page = ob_get_contents();
		ob_end_clean();
	}
}

?>