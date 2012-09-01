<?php

/* - - - - - - - - - - - - - - - - - - - - -

 Title : PHP Quick Profiler MySQL Class
 Author : Created by Ryan Campbell
 URL : http://particletree.com/features/php-quick-profiler/

 Last Updated : April 22, 2009

 Description : A simple database wrapper that includes
 logging of queries.

- - - - - - - - - - - - - - - - - - - - - */

class MySqlDatabase {

	private $host;			
	private $user;		
	private $password;	
	private $database;	
	public $queryCount = 0;
	public $queries = array();
	public $conn;
	
	/*------------------------------------
	          CONFIG CONNECTION
	------------------------------------*/
	
	function __construct($host, $user, $password) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		add_filter('query',array($this,'logQuery'));
		$self = $this;
		ActiveRecord\Config::initialize(function($cfg) use (&$self) {
			$cfg->set_logging(true);
			$cfg->set_logger($self);
		});
	}
	
	function connect($new = false) {
		$this->conn = mysql_connect($this->host, $this->user, $this->password, $new);
		if(!$this->conn) {
			throw new Exception('We\'re working on a few connection issues.');
		}
	}
	
	function changeDatabase($database) {
		$this->database = $database;
		if($this->conn) {
			if(!mysql_select_db($database, $this->conn)) {
				throw new CustomException('We\'re working on a few connection issues.');
			}
		}
	}
	
	function lazyLoadConnection() {
		$this->connect(true);
		if($this->database) $this->changeDatabase($this->database);
	}
	
	/*-----------------------------------
	   				QUERY
	------------------------------------*/
	
	function query($sql) {
		if(!$this->conn) $this->lazyLoadConnection();
		$start = $this->getTime();
		$rs = mysql_query($sql, $this->conn);
		
		$this->lastresult = array();
		$num_rows = 0;
		while ( $row = @mysql_fetch_object( $rs ) ) {
			$this->lastresult[$num_rows] = $row;
			$num_rows++;
		}
		//$row = mysql_fetch_array($rs);
		$this->queryCount += 1;
		$this->logQuery($sql, $start, strlen(serialize($this->lastresult)));
		if(!$rs) {
			//throw new Exception('Could not execute query.');
		}
		
		//return $rs;
		return $sql;
	}
	
	public function log($sql, $values)
	{
		foreach($values as $value) $sql = preg_replace('/\?/', $value, $sql, 1);
		//$this->query($sql);
		$this->queryCount += 1;
		$this->logQuery($sql, $this->getTime(), 0);
		if (SW_LOG_QUERIES === true) trigger_error($sql, E_USER_WARNING);
	}
	
	function fetch_array($sql) {
		$rs = mysql_query($sql, $this->conn);
		$row = mysql_fetch_array($rs, MYSQL_ASSOC);
		return $row;
	}
	
	/*-----------------------------------
	          	DEBUGGING
	------------------------------------*/
	
	function logQuery($sql, $start, $size) {
		$query = array(
				'sql' => $sql,
				'time' => ($this->getTime() - $start)*1000,
				'size' => $size
			);
		array_push($this->queries, $query);
		return $sql;
	}
	
	function getTime() {
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;
		return $start;
	}
	
	public function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret,3,'.','') . ' ' . $formats[$formatter];
		return $ret;
	}
	
	function __destruct()  {
		remove_filter('query',array(&$this,'query'));
		@mysql_close($this->conn);
	}
	
}

?>
