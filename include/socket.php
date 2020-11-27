#!/usr/local/bin/php
<?php

    require_once 'DB_Connect.php';

    // порт сокет сервера
    $port = 666;

    // необходима для метода socket_select
    $null = NULL;

    // создаём TCP/IP сокет
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

    // установка параметров для сокета
    // SOL_SOCKET - уровень протокола
    // SO_REUSEADDR - сообщает, что локальные адреса могут использоваться повторно
    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

    // привязываем сокет к имени и порту
    socket_bind($sock, 0, $port);

    // начать слушать сокет
    socket_listen($sock);

    // оповещение о старте демона
    echo "server started\n";

    // массив подключёенных клиентов
    $clients = array($sock);

    while (true) {
        // создание не модифицируемой копии $clients
        $read = $clients;

        // запускаем системный вызов для массива клиентов
        if (socket_select($read, $null, $null, NULL) < 1) {
            continue;
        }

        // авторизация нового сокет клиента
        if (in_array($sock, $read)) {

            $newsock = socket_accept($sock);

            //var_dump($newsock); // TEST RESOURSE
            // проверка пароля, передача соли
            socket_write($newsock, "take session\n");

            // прием строки сессии
            $pass = @socket_read($newsock, 1024, PHP_NORMAL_READ);
            // оповещение сервера о новом клиенте

            if (checkUserId(substr($pass, 0, -1))) {
              $clients[checkUserId(substr($pass, 0, -1))] = $newsock;

              socket_write($newsock, "connected\n");

              socket_getpeername($newsock, $ip);
              echo "connected ip: " . $ip . "  id: " . checkUserId(substr($pass, 0, -1)) . "\n";

              // поиск клиента в массиве
              $key = array_search($sock, $read);
              unset($read[$key]);
            } else {
              socket_write($newsock, "not correct\n");
              socket_close($newsock);

              continue;
            }
        }

        // перебор списка клиентов
        foreach ($read as $read_sock) {
            // Чтение сообщения
            $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

            // проверка клиента на онлайн
            if ($data === false) {
                // если клиент оффлайн удаление из списка
                $key = array_search($read_sock, $clients);
                unset($clients[$key]);
                echo "client id: " . $key . " disconnected.\n";
                // продолжить с оставшимся списком клиентов
                continue;
            }

            // чистим строку от пробелов
            $data = trim($data);

            // первые два символа в строке, для нахождения запроса на координаты
            $id_string = substr($data, 0, 2);

            //var_dump($id_string); // TEST RESOURSE!!!

            // игнорировать пустые строки
            if (!empty($data)) {
                switch ($data) {
                  case 'check':
                      socket_write($read_sock, "connected\n");
                    break;
                  default:
                    // остальное мы игнорируем, если это не запрос на координаты, об этом позже
                    if (is_numeric(substr($data, 0, 3))) {
                      resend(array('email' => 'hukutuh.ahtoh@yandex.ru', 'coord' => $data));
                    }
                    //echo $data;
                    break;
                }

                // если на сервере спросили координаты, разослать всем
                if ($id_string == "id") {
                    // оповещение сокета с номер водителя указанным в базе
                    $full = substr($data, 2);

                    if ($clients[$full]) {
                      socket_write($clients[$full], "u coord?\n");
                    } else {
                      echo "Driver not found!\n";
                    }
                }
            }
        }
    }

    // закрытие сокета
    socket_close($sock);

    // метод для пересылки полученых координат постом на другой сервер
    function resend($params) {
      $parts = parse_url('http://iwatercrm.ru/iwater_api/nusoap/include/socket.php');

	    if (!$fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80))
	    {
	        return false;
	    }

	    $data = http_build_query($params, '', '&');

	    fwrite($fp, "POST " . (!empty($parts['path']) ? $parts['path'] : '/') . " HTTP/1.1\r\n");
	    fwrite($fp, "Host: " . $parts['host'] . "\r\n");
	    fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
	    fwrite($fp, "Content-Length: " . strlen($data) . "\r\n");
	    fwrite($fp, "Connection: Close\r\n\r\n");
	    fwrite($fp, $data);
	    fclose($fp);

	    return true;
    }

    function checkUserId($session) {
      $db = new DB_Connect();
      $dbh = $db->connect_db();
      $dbh->query("SET NAMES 'UTF8'");

      $session = filter_var($session, FILTER_SANITIZE_SPECIAL_CHARS);

      // try {
        $res = $dbh->query("SELECT `id` FROM `iwater_driver` WHERE `session` = '$session'");
      // $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      // } catch (Exception $e) { return false; }

      $r = $res->fetch();

      return $r['id'];
    }
?>
