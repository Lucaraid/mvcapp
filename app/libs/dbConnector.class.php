<?php
/**
* Database Connection
*/
class dbConnector
{
	private static 	$_instance 	= null;
	private 		$_pdo,
					$_query,
					$_error 	= false,
					$_results,
					$_count 	= 0;

	private function __construct()
	{
		try {
			$this->_pdo = new PDO(
				CONFIG::get('mysql/type'). 					// Datenbank Typ
				':host='.CONFIG::get('mysql/host').			// Datenbank Host
				';port='.CONFIG::get('mysql/port').			// Datenbank Port
				';dbname='.CONFIG::get('mysql/config_db'),	// Datenbank Name
				CONFIG::get('mysql/user'), 					// Datenbank User
				CONFIG::get('mysql/pass'));					// Datenbank Passwort
		} catch (PDOException $e) {
			echo $e->getMessage() . '<br />';
			die('Verbindung zur Datenbank konnte nicht hergestellt werden.<br />Bitte sp&auml;ter nochamls probieren.');
		}
	}
	public static function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new dbConnector();
		}
		return self::$_instance;
	}

	public function error()
	{
		return $this->_error;
	}

	public function count()
	{
		return $this->_count;
	}

	public function results()
	{
		return $this->_results;
	}

	public function first()
	{
		return $this->results()[0];
	}

	public function where($where = array())
	{
		if(count($where) === 3) {
			$oporators = array('=', '>', '<', '>=', '<=');

			$field 		= $where[0];
			$operator 	= $where[1];
			$value 		= $where[2];

			if (in_array($operator, $oporators)) {
				$query = "{$field} {$operator} ?";
				return array('query' => $query, 'value' => $value); 
			}
		}
		return false;
	}

	public function query($query = '', $params = array()) 
	{
		$this->_error = false;
		if ($this->_query = $this->_pdo->prepare($query)) {
			$x = 1;
			if (count($params)) {
				foreach ($params as $param) {
					$this->_query->bindValue($x, $param);
					$x++;
				}
			}

			if ($this->_query->execute()) {
				$this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
				$this->_count = $this->_query->rowCount();
			} else {
				$this->_error = true;
			}
		}
		return $this;
	}

	private function action($action, $table, $where = array())
	{
		if($wherequery = $this->where($where)) {
			$query = "{$action} FROM {$table} WHERE {$wherequery['query']}";
			if (!$this->query($query, array($wherequery['value']))->error()) {
				return $this;
			}
		}
		return false;
	}

	public function get($table, $where)
	{
		return $this->action('SELECT *', $table, $where);
	}

	public function delete($table, $where)
	{
		return $this->action('DELETE', $table, $where);
	}

	public function insert($table, $fields = array())
	{
		if (count($fields)) {
			$keys = array_keys($fields);
			$values = '';
			$x = 1;

			foreach ($fields as $field) {
				$values .= '?';
				if ($x < count($fields)) {
					$values .= ', ';
				}
				$x++;
			}

			$query = "INSERT INTO {$table} (" . '`' . implode('`, `', $keys) . '`' . ") VALUES ({$values})";
			if(!$this->query($query, $fields)->error()) {
				return true;
			}
		}
		return false;
	}

	public function update($table, $fields, $where = array())
	{
		if($wherequery = $this->where($where)) {

			$set = '';
			$x = 1;

			foreach ($fields as $name => $value) {
				$set .= "{$name} = ?";
				if ($x < count($fields)) {
					$set .= ', ';
				}
				$x++;
			}

			$query = "UPDATE {$table} SET {$set} WHERE {$wherequery['query']}";
			$fields['where'] = $wherequery['value'];
			if (!$this->query($query, $fields)->error()) {
				return true;
			}
		}
		return false;
	}
}
?>