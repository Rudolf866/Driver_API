<?php
class DB_Connect {

	function connect_db() {


	    // $dsn = 'mysql:dbname=db_endicomp_27;charset=UTF8;host=endicomp.mysql';
	    // $user = 'dbu_endicomp_1';
		// $password = 'oHb0esMnA4z';

		$dsn = 'mysql:dbname=db_endicomp_6;charset=UTF8;host=endicomp.mysql';
	    $user = 'dbu_endicomp_6';
		$password = 'ncvhbuQ8mGy';


	    return new PDO($dsn, $user, $password);
	}
}

?>
