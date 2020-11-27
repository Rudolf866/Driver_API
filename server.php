<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once("include/DB_Functions.php");
require_once("nusoap.php");

$server = new soap_server();

$server->decode_utf8 = false;
$server->http_encoding = 'UTF-8';
$server->soap_defencoding = 'UTF-8';

$server->configureWSDL("IWater","urn:IWater");

/** Объявление операций
 */


$server->register("auth", array("company" => "xsd:string", "login" => "xsd:string", "password" => "xsd:string", "notification" => "xsd:string"), array("error" => "xsd:int", "return" => "xsd:string"),"urn:authuser","urn:authuser#auth", "", "encoded", "<br>&nbsp Authorization. <br>&nbsp Input: company id, user login, password, notification key. <br>&nbsp; Output: session. <br>&nbsp; Error list: {Input data not correct. Please try again!}");
$server->register("accept", array("id" => "xsd:int", "tank" => "xsd:int", "comment" => "xsd:string", "coord" => "xsd:string", "delinquency" => "xsd:string"), array("error" => "xsd:int", "return" => "xsd:string"), "urn:authuser", "urn:authuser#accept", "", "encoded", "<br>&nbsp Accept order. <br>&nbsp Input: order id, count returned tank, driver comment, driver coord. <br>&nbsp; Output: request status.");

$server->register("history", array("session" => "xsd:string"), array("return" => "xsd:sequence"), "urn:info", "urn:info#hist", "", "encoded", "<br>&nbsp Driver history list. <br>&nbsp Input: session. <br>&nbsp; Output: list.");
$server->register("todaylist", array("session" => "xsd:string"), array("return" => "xsd:sequence"), "urn:info", "urn:info#today", "", "encoded", "<br>&nbsp Driver list on current date. <br>&nbsp Input: session. <br>&nbsp; Output: list.");
//========= тестовая функция для водителя ==========
$server->register("drivertodaylist", array("id" => "xsd:string"), array("return" => "xsd:sequence"), "urn:info", "urn:info#drivertoday", "", "encoded", "<br>&nbsp Driver list on current date. <br>&nbsp Input: session. <br>&nbsp; Output: list.");
$server->register("testwaybill", array("session" => "xsd:string", "id" => "xsd:string"), array("return" => "xsd:sequence"), "urn:info", "urn:info#testlist", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
$server->register("ordersCurrent", array("id" => "xsd:int"), array("return" => "xsd:sequence"), "urn:info", "urn:info#ordersCurrent", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
$server->register("infoCurrent", array("id" => "xsd:int"), array("return" => "xsd:sequence"), "urn:info", "urn:info#infoCurrent", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
$server->register("periodCurrent", array("id" => "xsd:int"), array("return" => "xsd:sequence"), "urn:info", "urn:info#periodCurrent", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
$server->register("typeClient", array("id" => "xsd:int"), array("return" => "xsd:sequence"), "urn:info", "urn:info#typeClient", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
$server->register("td_list_All", array("id" => "xsd:int"), array("return" => "xsd:sequence"), "urn:info", "urn:info#td_list_All", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
$server->register("travelId", array("id" => "xsd:int"), array("return" => "xsd:sequence"), "urn:info", "urn:info#travelId", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");

$server->register("DRname", array("id" => "xsd:int"), array("return" => "xsd:sequence"), "urn:info", "urn:info#DRname", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
//================================================
$server->register("waybill", array("session" => "xsd:string", "id" => "xsd:string"), array("return" => "xsd:sequence"), "urn:info", "urn:info#list", "", "encoded", "<br>&nbsp Driver list on selected id. <br>&nbsp Input: date. <br>&nbsp; Output: list.");
$server->register("blackout", array("yea" => "xsd:int"), array("return" => "xsd:string"), "urn:out", "urn:out#black", "", "encoded", "<br>&nbsp Set ststus = 0 on all orders");
$server->register("dateout", array("yea" => "xsd:int"), array("return" => "xsd:string"), "urn:out", "urn:out#date", "", "encoded", "<br>&nbsp Set ststus = 0 on all orders");

/** Авторизация пользователя
 */
function auth($company, $login, $password, $notification) {
	$func = new DB_Functions();

	$user = $func->auth($login, $password, $company, $notification);

	if ($user != false) {
		/**	Авторизация прошла успешно
		 */
		return array("error" => 0, "return" => '<session>' . $user['session'] . '</session><id>' . $user['id'] . '</id>');
	} else {
		/**	Ошибка данных
		 */
		return array("error" => 1, "return" => "Error: Input data not correct. Please try again!");
	}
}

/** Подтверждение получения заказа
 */
function accept($id, $tank, $comment, $coord, $delinquency) {
	$func = new DB_Functions();

	$stat = $func->orderAccept($id, $tank, $comment, $coord, $delinquency);

	if ($stat != false) {
		/** Заказ подтверждён
		 */
		return array("error" => 0, "return" => "Success.");
	} else {
		/** Ошибка ввода
		 */
		return array("error" => 1, "return" => "Unfound error.");
	}
}

function history($session) {
	$func = new DB_Functions();

	$date = $func->driverHistory($session);

	if ($date) {
		/** Успешный ответ
		 */
		$output_str = '';

		foreach ($date as $key => $value) {
			$output_str .= '
			<id>' . $value . '</id>
			<date>' . $key . '</date>';
		}

		return '<history>' . $output_str . '
		</history>';
	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}

//==========================

//тестовая функция
function DRname($id) {
	$func = new DB_Functions();
	$date = $func->idTravel($id);

	if ($date) {
		/** Успешный ответ
		 */

		$output_str = '';

		$output_str .= '
			 <period>' . $date. '</period>';

		return '<info_period>' . $output_str . '
		</info_period>';

	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}
//$res = DRname(94759);
//$res = DRname();
//print_r($res);
// $res = DRname(1);
 //print_r($res);

//================ДЛя водителей ANDROID========================

// Получить все заказы на сегодня по id клиента

function ordersCurrent($id)
{
	$func = new DB_Functions();

	$mass = '';

	$date = $func->currentOrders($id);

	if ($date) {

		$index =0;

		$elements = count ($date);

		while ($index < $elements) {

			$mass .='<id_orders>'.$date[$index].'</id_orders>';
			$index++;
		}

		return '<info_period>' . $mass . '
		</info_period>';
	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}
//Получить id клиента и координаты за сегодня по id заказа

function infoCurrent($id) {
	$func = new DB_Functions();

	$date = $func->currentInfo($id);

	if ($date) {
		/** Успешный ответ
		 */
		$output_str = '';

		$output_str .= '
			<coords>' . $date['coords']. '</coords>
			<client_id>' . $date['client_id']. '</client_id>';

		return '<info>' . $output_str . '
		</info>';
	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}

// Получение периода от заказа за сегодня

function periodCurrent($id) {
	$func = new DB_Functions();
	$date = $func->currPeriod($id);

	if ($date) {
		/** Успешный ответ
		 */

		$output_str = '';

		$output_str .= '
			<id_orders>' . $date['id']. '</id_orders>
			<period>' . $date['period']. '</period>';

		return '<info_period>' . $output_str . '
		</info_period>';
	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}
//получение типа клиента по id заказа
function typeClient($id)
{
	$func = new DB_Functions();
	$date = $func->clientType($id);

	if ($date == '1') {
		/** Успешный ответ
		 */

		$output_str = '';

		$output_str .= '
			 <period>' . $date. '</period>';

		return '<info_period>' . $output_str . '
		</info_period>';

	}  else if ($date == '0'){
		$output_int = 0;

		return '<info_period>' . $output_int. '
		</info_period>';

	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}

}

//==================================================
// все путевые листы за сегодня
function td_list_All()
{
	$func = new DB_Functions();
	$date = $func->day_lists();

	if ($date) {
		/** Успешный ответ
		 */

		$output_str = '';

		$output_str .= '
			 <period>' . $date. '</period>';

		return '<info_period>' . $output_str . '
		</info_period>';

	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}
//$res = td_list_All();
//print_r($res);

//заказы за сегодня по путевому листу
function travelId($id)
{
	$func = new DB_Functions();
	$date = $func->idTravel($id);

	if ($date) {
		/** Успешный ответ
		 */

		$output_str = '';

		$output_str .= '
			 <period>' . $date. '</period>';

		return '<info_period>' . $output_str . '
		</info_period>';

	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}
$res = travelId(1);
print_r($res);
function todaylist($session) {
	$func = new DB_Functions();

	$date = $func->todayList($session);

	if ($date) {
		/** Успешный ответ
		 */
		return '<id>' . $date . '
		</id>';
	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}
//  переопределяем функцию для водитяля по id
function drivertodaylist($id) {
	$func = new DB_Functions();

	$date = $func->drivertodayList($id);

	if ($date) {
		/** Успешный ответ
		 */
		return '<id>' . $date . '
		</id>';
	} else {
		/** Ошибка
		 */
		return array("return" => "Not correct data");
	}
}
/** Получение путевого листа по сессии водителя
 */
function waybill ($session, $id) {
	$func = new DB_Functions();

	$list = $func->getDriverList($session, $id);

	$mass = '';

	foreach ($list as $key => $value) {
		$mass .= "
 		<id>" . $value['id'] . "</id>
		<name>" . $value['name'] . "</name>
		<order>" . $value['order'] . "</order>
		<cash>" . $value['cash'] . "</cash>
		<contact>" . $value['contact'] . "</contact>
		<notice>" . $value['notice'] . "</notice>
 		<date>" .  $value['date'] . "</date>
 		<period>" . $value['period'] . "</period>
 		<address>" . $value['address'] . "</address>
		<coords>" . $value['coords'] . "</coords>
		<status>" . $value['status'] . "</status>";
	}

	return '<list>' . $mass . '
 	</list>';
}


function testwaybill ($session, $id) {
	$func = new DB_Functions();

	$list = $func->getDriverList($session, $id);

	$mass = '';

	foreach ($list as $key => $value) {
		$mass .= "
 		<id>" . $value['id'] . "</id>
		<name>" . $value['name'] . "</name>
		<order>" . $value['order'] . "</order>
		<cash>" . $value['cash'] . "</cash>
		<cash>" . $value['cash_b'] . "</cash>
		<time>". $value['time'] . "</time>
		<contact>" . $value['contact'] . "</contact>
		<notice>" . $value['notice'] . "</notice>
 		<date>" .  $value['date'] . "</date>
 		<period>" . $value['period'] . "</period>
 		<address>" . $value['address'] . "</address>
		<coords>" . $value['coords'] . "</coords>
		<status>" . $value['status'] . "</status>";
	}

	return '<list>' . $mass . '
 	</list>';
}



/** Метод, чтобы меня не трогали
 */
function blackout($yea) {
	$func = new DB_Functions();
	$info = $func->blackOut($yea);

	if ($info) {
		return 'Всё готово!';
	} else {
		return 'Что-то пошло не так!';
	}
}

/** Делает 4 заказа на текущую дату
 */
function dateout($yea) {
	$func = new DB_Functions();
	$result = $func->dateUpdater($yea);

	if ($result) {
		return 'Всё готов!';
	} else {
		return 'Что-то пошло не так!';
	}
}

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>
