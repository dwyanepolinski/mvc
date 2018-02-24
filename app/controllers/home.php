<?php

class Home extends Controller{

	public function index($args=[]){

		// echo var_dump($args);

		// Example usage of mysql support	
		$Item = $this->model('Item');
		$randomItems = $Item->get()->random()->limit(4)->run();

		$tdim = ['x' => 1, 2 => 'y', '3' => [1, 2, 3]];
		$template_data = ['ok' => 1, 'products' => 'pro', 'project_name' => 1, 'var' => 'some string', 'list' => $tdim, 'xlist' => [9, 8, 6]];
		// $template_data = ['x' => 'someval'];
		$this->view('home/index', $template_data);
	}
}

?>