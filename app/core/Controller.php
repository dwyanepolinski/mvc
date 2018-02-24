<?php

class Controller{
	protected function model($model){
		require_once __DIR__.'/../models/'.$model.'.php';
		return new $model();
	}

	protected function view($view, $data = []){

		$x = new Jinja($view, $data);
		$x->render();

		exit();

		// $template = (new Render($view))->page;

		// preg_match_all('{{ extends (.{1,20})\/([^\/]{1,20}) }}', $template, $extension_list);

		// foreach ($extension_list[0] as $index => $tag){
		// 	$subtemplate = (new Render($extension_list[1][$index].'/'.$extension_list[2][$index]))->page;
		// 	$template = str_replace('{'.$tag.'}', $subtemplate, $template);
		// }
		
		// foreach ($data as $key => $value) {
		// 	if(in_array(gettype($value), array('string', 'double', 'integer'))){
		// 		$template = str_replace('{{ '.$key.' }}', $value, $template);
		// 	}
		// 	if(is_array($value))
		// 		$template = str_replace('{{ '.$key.' }}', print_r($value, true), $template);
		// }

		echo $template;
		// require_once __DIR__.'/../views/'.$view.'.php';
	}
}

?>