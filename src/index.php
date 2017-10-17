<?php
    use \wkakis;

    define('IN_WKAKIS', true);
    require_once('./wkakis.config.php');
    require_once('./lib/Database.class.php');
    $db = new wkakis\Database($config['db']);
    $results = $db->update(array(
        'update'=> 'xd',
        'set'=> array(
            'id'=> 2137
        ),
        'where'=> 'id=2137'
    ));
    print_r($results->errorCode());
