<?php

/**
* 	Mysql engine by Marcin Poliński
*	mail: dwyanepolinski@gmail.com
*/

class Mysql{
	private $connection = NULL;

	// variables for SELECT queries
	private $query = '';
	private $queryValues = [];
	private $queryTypes = '';

	// variables for model information storing
	public $id = NULL;
	private $fields = [];
	private $child = '';
	private $types = 'i';
	
	public function __construct(){
		$this->connect();
		$this->getFields();
		$this->child = get_class($this);
		$this->id = $this->autoIncrement();

		if(func_num_args() == 1){
			$values = func_get_arg(0);
			foreach ($values as $key => $value)
				$this->$key = $value;
		}

		if(method_exists($this, 'setTypes'))
			$this->checkTypes($this->setTypes());
		else
			die('MysqlEngine::Error: Types not defined (define public function setTypes())');

		if(method_exists($this, 'construct'))
			$this->construct();
	}

	public function __destruct(){
		$this->connection->close();
	}

	private function connect(){
		$db_settings = (new Settings)->db;
		$this->connection = new mysqli(
			$db_settings['db_host'],
			$db_settings['db_user'],
			$db_settings['db_passwd'],
			$db_settings['db_name']);

		if ($this->connection->connect_error)
			die("Connection failed: " . $this->connection->connect_error);
	}

	private function exec($query, $properties = NULL, $types = NULL, $values = NULL){
		if(!$query)
			return NULL;

		if(!$types)
			$types = $this->types;

		if(!$values)
			foreach ($this->fields as $key)
				$values[$key] = $this->$key;

		$statement = $this->connection->prepare($query);

		if(!$statement)
			die('MysqlEngine::Error: Check your query syntax ('.$query.')');

		if($properties){
			$callables = [];
			foreach ($properties as $key => $value){
				$prop = $properties[$key];
				$callables[$key] = &$values[$prop];
			}

			call_user_func_array(array($statement, 'bind_param'), array_merge([$types], $callables));
		}

		$statement->execute();
		$result = $statement->get_result();
		$statement->close();

		return $result;
	}

	public function save(){
		if(!$this->tableExist($this->child))
			die('Table \''.$this->child.'\' not exist');

		$amount = count($this->fields);

		if($this->exist()){
			$columns = substr(str_repeat("`%s`=?, ", $amount-1), 0, -2);
			$query = sprintf('UPDATE `%s` SET %s WHERE `%s`.`id`=?', $this->child, $columns, $this->child);

			$tmpFields = $this->fields;
			unset($tmpFields[0]);
			$tmpFields = array_merge($tmpFields, ['id']);

			$query = vsprintf($query, $tmpFields);
			$types = substr($this->types, 1, strlen($this->types)).'i';

			$this->exec($query, $tmpFields, $types);
		} else {
			$columns = substr(str_repeat("`%s`, ", $amount), 0, -2);
			$values = substr(str_repeat("?, ", $amount), 0, -2);

			$query = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", $this->child, $columns, $values);
			$query = vsprintf($query, $this->fields);
		
			$this->exec($query, $this->fields);
		}
	}

	public function get(){
		if(!func_num_args())
			$fields = ['*'];
		else{
			$fields = func_get_arg(0);

			if(gettype($fields) == 'string')
				$fields = [$fields];
		}

		if($fields[0] == '*')
			$this->query = sprintf('SELECT * FROM `%s` ', $this->child);
		else {
			$columns = substr(str_repeat("`%s`, ", count($fields)), 0, -2); 
			$this->query = sprintf('SELECT %s FROM `%s` ', $columns, $this->child);
			$this->query = vsprintf($this->query, $fields);
		}
		return $this;
	}

	public function limit($number){
		if(is_int($number))
			$this->query .= sprintf('LIMIT %d ', $number);
		return $this;
	}

	public function random(){
		$this->query .= 'ORDER BY rand() ';
		return $this;
	}

	public function orderby($column){
		$this->checkCondition($column);
		$this->query .= sprintf('ORDER BY `%s` ', $column);
		return $this;
	}

	public function delete(){
		$this->query = sprintf('DELETE FROM `%s` ', $this->child);
		return $this;
	}

	public function where($column, $condition, $value){
		return $this->whereAndOr($column, $condition, $value, 'WHERE ');
	}

	public function and($column, $condition, $value){
		return $this->whereAndOr($column, $condition, $value, 'AND ');
	}

	public function or($column, $condition, $value){
		return $this->whereAndOr($column, $condition, $value, 'OR ');
	}

	private function whereAndOr($column, $condition, $value, $operation){
		$this->checkCondition($column);
		$this->query .= sprintf('%s `%s`%s? ', $operation, $column, $condition);
		$this->queryValues[$column] = $value;
		return $this;
	}

	public function run(){
		foreach ($this->queryValues as $key => $value)
			$this->queryTypes .= $this->types[array_search($key, $this->fields)];
		$result = $this->exec($this->query, array_keys($this->queryValues), $this->queryTypes, $this->queryValues);
		$this->query = '';
		$this->queryValues = [];
		$this->queryTypes = '';
		return $result;
	}

	private function checkCondition($column){
		if(!in_array($column, $this->fields))
			die('MysqlEngine::Error: where() cant recognize given field');
	}

	private function checkTypes($types){
		if(gettype($types) != 'string')
			die('MysqlEngine::Error: setTypes() must return a string');
		if(strlen($types) != count($this->fields)-1)
			die('MysqlEngine::Error: The number of types is not equal to the number of properties');
		foreach(str_split($types) as $type) {
			if(!in_array($type, ['s', 'd', 'i']))
				die('MysqlEngine::Error: Fount unrecognized type');
		}
		$this->types .= $types;
	}

	private function getFields(){
		$ref = new ReflectionClass($this);
		$properties = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property)
			array_push($this->fields, $property->getName());
		unset($this->fields[array_search('id', $this->fields)]);
		$this->fields = array_merge(['id'], $this->fields);
	}

	protected function autoIncrement(){
		$result = $this->exec(sprintf("SHOW TABLE STATUS LIKE '%s'", $this->child));
		$data = mysqli_fetch_assoc($result);
		return intval($data['Auto_increment']);
	}

	private function exist(){
		$query = sprintf("SELECT * FROM `%s` WHERE `id`=?", $this->child);
		return mysqli_num_rows($this->exec($query, ['id'], 'i'));
	}

	private function tableExist($table){
		return mysqli_num_rows($this->exec(sprintf("SHOW TABLES LIKE '%s'", $table)));
	}
}

?>