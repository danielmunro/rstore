rstore
========

A simple redis-backed repository for php.

usage
-----

```php
<?php

require_once 'vendor/autoload.php';

$client = new Predis\Client(
    array(
        'host' => '127.0.0.1',
        'port' => '6379'
    )
);

$repo = new Rstore\Repository(
    $client,
    yaml_parse(
"user:
    id:
        type: integer
        index: true
    full_name:
        type: string
        maxlength: 255
        required: true
    username:
        type: string
        index: true
    password:
        type: string
        required: true
    email_addresses:
        type: array
    bio:
        type: string"
    )
);

$user = $repo->create('user');
$user->full_name = "John Doe";
$user->username = "john_doe";
$user->password = "...";
$user->email_addresses = array(
    "john.doe@provider.net",
    "john.doe@another-provider.com"
);

$repo->save($user);

$loadedUser = $repo->loadByIndex('user', 'id', $user->id);

echo $loadedUser == $user; // true
```

generated docs
--------------

http://rstore-docs.danmunro.com/
