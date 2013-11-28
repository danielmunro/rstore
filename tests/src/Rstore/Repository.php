<?php

use Rstore\Repository,
    Predis\Client;

class RepositoryTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->repo = new Repository(
            new Client(
                array(
                    'host' => '127.0.0.1',
                    'port' => 6379
                )
            ),
            yaml_parse_file(__DIR__.'/../../config/models.yaml')
        );
    }

    public function teardown() {
        //$this->repo->connection->flushdb();
    }

    /**
     * @expectedException Rstore\Exception\ModelNotFound
     */
    public function testCreate() {
        $user = $this->repo->create('user');
        $this->assertInstanceOf('stdClass', $user);

        $this->assertTrue(is_numeric($user->age));
        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue(is_string($user->name));
        $this->assertTrue(is_array($user->articles));
        $this->assertTrue(is_null($user->favorite_article));
        $this->assertEquals($user->name, 'user');

        $article = $this->repo->create('article');
        $this->assertInstanceOf('stdClass', $article);
        $this->assertEquals($article->title, "New article");

        $this->repo->create('foobar_not_exists');
    }

    public function testPersist() {
        $user = $this->repo->create('user');
        $user->articles = array(
            $this->repo->create('article', array(
                'url' => '/test-article-1',
                'article' => 'test 1'
            )),
            $this->repo->create('article', array(
                'url' => '/test-article-2',
                'article' => 'test 2'
            )),
        );
        $user->favorite_article = $this->repo->create('article', array(
            'url' => '/test-article-3',
            'article' => 'test 3'
        ));
        $user->email_addresses = array(
            'john.doe@provider.net',
            'john.doe2@provider2.net'
        );
        $this->repo->save($user);
        $this->assertGreaterThan(0, $user->id);

        $loadedUser = $this->repo->loadByIndex('user', 'id', $user->id);
        $this->assertEquals($user, $loadedUser);
    }
}
