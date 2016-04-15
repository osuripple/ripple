<?php

// This class is a modified version of https://github.com/a1phanumeric/PHP-MySQL-Class/blob/master/class.DBPDO.php, which is licensed under the GNU GPL3.
// This version has 2 essential tweaks:
// uses DATABASE_WHAT to switch between host, a classical TCP connection usually to 127.0.0.1, and a UNIX socket, which is usually way faster.
// @execute, @fetch, @fetchAll: the operators == are changed to === to be more strict and allow for values like 0 to be inserted inside the database without having to wrap them inside an array on the function calling.
class DBPDO {
	public $pdo;
	private $error;

	public function __construct() {
		$this->connect();
	}

	public function prep_query($query) {
		return $this->pdo->prepare($query);
	}

	public function connect() {
		if (!$this->pdo) {
			$dsn = 'mysql:dbname='.DATABASE_NAME.';'.DATABASE_WHAT.'='.DATABASE_HOST;
			$user = DATABASE_USER;
			$password = DATABASE_PASS;
			try {
				$this->pdo = new PDO($dsn, $user, $password, [PDO::ATTR_PERSISTENT => true]);

				return true;
			}
			catch(PDOException $e) {
				$this->error = $e->getMessage();
				die($this->error);

				return false;
			}
		} else {
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

			return true;
		}
	}

	public function table_exists($table_name) {
		$stmt = $this->prep_query('SHOW TABLES LIKE ?');
		$stmt->execute([$this->add_table_prefix($table_name)]);

		return $stmt->rowCount() > 0;
	}

	public function execute($query, $values = null) {
		if ($values === null) {
			$values = [];
		} elseif (!is_array($values)) {
			$values = [$values];
		}
		$stmt = $this->prep_query($query);
		$stmt->execute($values);

		return $stmt;
	}

	public function fetch($query, $values = null) {
		if ($values === null) {
			$values = [];
		} elseif (!is_array($values)) {
			$values = [$values];
		}
		$stmt = $this->execute($query, $values);

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function fetchAll($query, $values = null, $key = null) {
		if ($values === null) {
			$values = [];
		} elseif (!is_array($values)) {
			$values = [$values];
		}
		$stmt = $this->execute($query, $values);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		// Allows the user to retrieve results using a
		// column from the results as a key for the array
		if ($key != null && $results[0][$key]) {
			$keyed_results = [];
			foreach ($results as $result) {
				$keyed_results[$result[$key]] = $result;
			}
			$results = $keyed_results;
		}

		return $results;
	}

	public function lastInsertId() {
		return $this->pdo->lastInsertId();
	}
}
