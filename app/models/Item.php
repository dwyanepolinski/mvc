<?php

/**
	Mysql class for PHP MVC by dwyanepolinski@gmail.com

	- INSTALATION:
		just add require section to Init.php file

	- USAGE:
		extend your model class using Mysql class. Set model (table) structure by typing
		class properties (must be public!). Instead of using standard constructor in your
		class (like public function __construct(){}), use construct() function. It is 
		required to proper funcionality of Mysql class. You must define setTypes() function
		that will return a string mask of types for your properties (s - string, d - double, i - int)
		Example: return 'issd';
*/

class Item extends Mysql{
	public $name;
	public $price;
	public $description;
	public $image;

	public function setTypes(){ return 'ssss'; }
}


?>