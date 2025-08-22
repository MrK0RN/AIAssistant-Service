
<?php
include_once "log.php";
error_reporting(E_ALL & ~E_WARNING);

function pgQuery($sql, $count=false, $returning=false) {
    // Docker environment configuration
    $host = getenv('DB_HOST') ?: 'db';
    $dbname = getenv('DB_NAME') ?: 'ai_assistant_db';
    $user = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: 'postgres123';
    $port = getenv('DB_PORT') ?: '5432';
    
    $connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
    
    $connection = pg_connect($connection_string);
    $stat = pg_connection_status($connection);
    
    if ($stat === PGSQL_CONNECTION_BAD) {
        logger("server", "connection_is_requested", "FAILURE");
        return false;
    } elseif ($stat === PGSQL_CONNECTION_OK) {
        logger("server", "connection_is_requested", "SUCCESS");
    }

    logger("server", "request_send", $sql);
    $result = pg_query($connection, $sql);
    $res = true;
    
    if ($result === false) {
        logger("DB", "response", "is false");
        $res = false;
    } elseif ($count) {
        $res = pg_num_rows($result);
    } elseif (stripos($sql, 'SELECT') === 0 || $returning) {
        $res = [];
        while ($row = pg_fetch_assoc($result)) {
            $res[] = $row;
        }
    }

    if ($res){
        logger("server", "report", "data gathered");
    }
    
    pg_close($connection);
    return $res;
}
?>
