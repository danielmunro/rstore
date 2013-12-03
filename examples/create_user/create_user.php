<?php

require_once __DIR__.'/../../vendor/autoload.php';

$client = new Predis\Client(
    array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => '11'
    )
);

// for safety, comment this out
//$client->flushdb();

$repo = new Rstore\Repository($client, yaml_parse_file(__DIR__.'/models.yaml'));

$user = $repo->create('user', array(
    'full_name' => 'John Doe',
    'handle' => 'john_doe',
    'email_addresses' => array(
        'john.doe@provider.net',
        'doe.john@provider.net'
    ),
    'age' => 100
));

$user->articles = array(
    $repo->create('article', array(
        'url' => '/test-article-1'
    )),
    $repo->create('article', array(
        'url' => '/test-article-2'
    )),
    $repo->create('article', array(
        'url' => '/test-article-3'
    )),
    $repo->create('article', array(
        'url' => '/test-article-4'
    ))
);

$repo->save($user);

$userLoadedByID = $repo->loadByIndex('user', 'id', $user->id);
$userLoadedByHandle = $repo->loadByIndex('user', 'handle', $user->handle);

echo ($user == $userLoadedByID).PHP_EOL;
echo ($user == $userLoadedByHandle).PHP_EOL;
