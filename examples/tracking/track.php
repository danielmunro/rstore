<?php

// SETUP EXAMPLE DATA

$_SERVER['HTTP_REFERRER'] = 'http://affiliate.com/?data1=affiliate_code&data2=test&data3=some_code2&data4=random_identifier';

$_SERVER['REMOTE_ADDR'] = '1.1.1.1';

$_GET = array(
    'data1' => 'affiliate_code',
    'data2' => 'test',
    'data3' => 'some_code2',
    'data4' => 'random_identifier'
);

// END SETUP

require_once __DIR__.'/../../vendor/autoload.php';

$connection = new Predis\Client(
    array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => '11'
    )
);
$adapter = new Rstore\ConnectionAdapter\Predis($connection);

// for safety, comment this out
//$client->flushdb();

$repo = new Rstore\Repository($adapter, yaml_parse_file(__DIR__.'/models.yaml'));

$event = $repo->create('tracking_event');

if(isset($_SERVER['HTTP_REFERRER'])) {
    $event->referrer = $_SERVER['HTTP_REFERRER'];
}

/**
 * Associative arrays are treated differently than numeric-index arrays. Only
 * numeric-index arrays with a standard key range may be saved as model type
 * 'array'. Otherwise, there should be a model defined for the data, such as
 * 'affiliate_codes', so that the keys are maintained correctly.
 */
$event->affiliate_codes = $repo->create('affiliate_codes', $_GET);

$repo->save($event);

$loadedEvent = $repo->loadByIndex('tracking_event', 'id', $event->id);

echo ($loadedEvent == $event).PHP_EOL;
