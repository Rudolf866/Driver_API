#!/usr/local/bin/php
<?php
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set("memory_limit","32M");

    require_once 'DB_Connect.php'; // подключение к базе

    $socket_address = '127.0.0.1'; // адрес сокет сервера
    $socket_port = 666;  // порт сокет сервера
    $null = NULL; // необходима для метода socket_select

    $socket; // сокет
    $asker; // id пользователя спросившего координаты

    create(); // создаёv

    function create() {
      // создаём TCP/IP сокет
      $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      if ($socket === false) {
          onException('Socket server not started');
          return;
      }
      // установка параметров для сокета
      // SOL_SOCKET - уровень протокола
      // SO_REUSEADDR - сообщает, что локальные адреса могут использоваться повторно
      socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

      // привязываем сокет к имени и порту
      if (socket_bind($socket, 0, $socket_port) === false) {
          onException('Socket server not binded');
          return;
      }

      listener();
    }

    function listener() {
      // начать слушать сокет
      if (socket_listen($socket) === false) {
          onException('Server not start listen');
          return;
      }
      // оповещение о старте демона
      echo "server started\n";
      // массив подключёенных клиентов
      $clients = array($socket);

      while (true) {
          $read = $clients; // создание не модифицируемой копии $clients
          if (socket_select($read, $null, $null, NULL) < 1) {
              continue;
          } // запускаем системный вызов для массива клиентов

          // авторизация нового сокет клиента
          if (in_array($socket, $read)) {

              if ($newsock = socket_accept($socket) === false) {
                  onException('Socket not accept');
                  return;
              }

              //var_dump($newsock); // TEST RESOURSE
              // проверка пароля, передача соли
              if (socket_write($newsock, "take session") === false) {
                  onException('Not asked user session');
                  return;
              }

              // прием строки сессии
              if ($pass = @socket_read($newsock, 1024, PHP_NORMAL_READ) === false) {
                  onException('Socket not read request');
                  return;
              }
              // оповещение сервера о новом клиенте

              if (checkUserId(substr($pass, 0, -1))) {
                $clients[checkUserId(substr($pass, 0, -1))] = $newsock;

                if (socket_write($newsock, "connected") === false) {
                    onException('Not tell about connection');
                    return;
                }

                socket_getpeername($newsock, $ip);
                echo "connected ip: " . $ip . "  id: " . checkUserId(substr($pass, 0, -1)) . "\n";

                // поиск клиента в массиве
                $key = array_search($socket, $read);
                unset($read[$key]);
              } else {
                if (socket_write($newsock, "not correct") === false) {
                    onException('Not tell about bad session');
                    return;
                }
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

              // игнорировать пустые строки
              if (!empty($data)) {
                  switch ($data) {
                    case 'check':
                        socket_write($read_sock, "connected");
                      break;
                    default:
                      // остальное мы игнорируем, если это не запрос на координаты, об этом позже
                      if (is_numeric(substr($data, 0, 3))) {
                        socket_write($asker, 'data');
                      }
                      //echo $data;
                      break;
                  }

                  // если на сервере спросили координаты, разослать всем
                  if ($id_string == "id") {
                      // оповещение сокета с номер водителя указанным в базе
                      $full = substr($data, 2);

                      if ($clients[$full]) {
                        socket_write($clients[$full], "ucoord?");
                        $asker = $read_sock;
                      } else {
                        socket_write($read_sock, 'offline');
                      }
                  }
              }
          }
      }
    }

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

    // чтение id пользователя по сессии
    function checkUserId($session) {
        $db = new DB_Connect();
        $dbh = $db->connect_db();
        $dbh->query("SET NAMES 'UTF8'");

        $session = filter_var($session, FILTER_SANITIZE_SPECIAL_CHARS);

        // try {
            $res = $dbh->query("SELECT `id` FROM `iwater_driver` WHERE `session` = '$session'");
        //    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //} catch (Exception $e) {
        //    onException($e);
        //}

        $r = $res->fetch();

        return $r['id'];
    }

    // в случае ошибки, пишем о ней в файл и перезапускам сервер
    function onException($value) {
      socket_close($this->socket); // закрываем сокет
      logger($value); // пишем ошибку в файл
      create(); // создаём
    }

    // запись ошибок в отдельный файл
    function logger($error_msg) {
      $date = date('Y-m-d H:i:s (T)');
      $file = fopen('errors.txt', 'a');
      $system_error_code = socket_last_error();
      $system_error_msg = socket_strerror($errorcode);

      if (!empty($file)) {
        $err  = $date . " - " . $error_msg . " { " . $system_error_msg . " }\r\n";
        fwrite($file, $err);
        fclose($file);
      }
    }
?>
