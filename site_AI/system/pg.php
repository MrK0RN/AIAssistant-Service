<?php
include "log.php";
error_reporting(E_ALL & ~E_WARNING);

function pgQuery($sql, $count=false, $returning=false) {
    // Нужно ввести свои данные
    $host = "postgres";  // Используем имя сервиса из docker-compose
    $dbname = "AI_site";
    $user = "AI_site_owner";
    $password = "npg_EKb1oN9DaPht";
    $dbport = "5432";    // Стандартный порт PostgreSQL

    $connection_string = "host=".$host." port=".$dbport." dbname=".$dbname." user=".$user." password=".$password;

    $connection = pg_connect($connection_string);

    if ($connection === false) {
        logger("server", "connection_is_requested", "FAILURE: " . pg_last_error());
        return false;
    }

    $stat = pg_connection_status($connection);
    if ($stat === PGSQL_CONNECTION_BAD) {
        logger("server", "connection_is_requested", "FAILURE");
        pg_close($connection);
        return false;
    }

    logger("server", "connection_is_requested", "SUCCESS");
    logger("server", "request_send", $sql);

    $result = pg_query($connection, $sql);
    $res = true;

    if ($result === false) {
        logger("DB", "response", "Query failed: " . pg_last_error($connection));
        $res = false;
    } elseif ($count) {
        $res = pg_num_rows($result);
    } elseif (stripos($sql, 'SELECT') === 0 || $returning) {
        $res = [];
        while ($row = pg_fetch_assoc($result)) {
            $res[] = $row;
        }
    }

    if ($res) {
        logger("server", "report", "data gathered");
    }

    pg_close($connection);
    return $res;
}
?>