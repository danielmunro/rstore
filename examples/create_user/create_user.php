<?php

require_once __DIR__.'/../../vendor/autoload.php';

$client = new Predis\Client(
    array(
        'host' => '127.0.0.1',
        'port' => 6379
    )
);

$repo = new Rstore\Repository($client, yaml_parse_file(__DIR__.'/models.yaml'));

$user = $repo->create('user');

$user->full_name = "Dan Munro";
$user->handle = "danmunro";
$user->email = "dan@danmunro.com";
$user->age = 100;

$user->articles = array(
    $repo->create('article'),
    $repo->create('article'),
    $repo->create('article'),
    $repo->create('article'),
);

$repo->save($user);

$user = $repo->loadByIndex('user', 'id', $user->id);

var_dump($user);
