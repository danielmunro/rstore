<?php

use Rstore\Repository,
    Rstore\ConnectionAdapter\Predis as PredisAdapter,
    Predis\Client;

class RepositoryTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        /**
        $connection = new Client(
            array(
                'host' => '127.0.0.1',
                'port' => 6379,
                'db' => '12'
            )
        );
        $adapter = new PredisAdapter($connection);
         */

        $connection = new \Redis();
        $connection->connect('127.0.0.1', 6379);
        $connection->select(11);
        $adapter = new Rstore\ConnectionAdapter\Phpredis($connection);

        $this->connection = $connection;
        $this->repo = new Repository(
            $adapter,
            yaml_parse_file(__DIR__.'/../../config/models.yaml')
        );
    }

    public function teardown() {
        $this->connection->flushdb();
    }

    protected function getArticles() {
        return array(
            $this->repo->create('article', array(
                'title' => 'Starting titles with different letters',
                'url' => '/test-article-1',
                'article' => 'test 1'
            )),
            $this->repo->create('article', array(
                'title' => 'In order to test sorting',
                'url' => '/test-article-2',
                'article' => 'test 2'
            )),
            $this->repo->create('article', array(
                'title' => 'Functionality',
                'url' => '/test-article-3',
                'article' => 'test 3'
            )),
        );
    }

    /**
     * @expectedException Rstore\Exception\ModelNotFound
     */
    public function testCreate() {
        $user = $this->repo->create('user', array('handle' => 'anon'));
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
        $user = $this->repo->create('user', array('handle' => 'anon'));
        $user->articles = $this->getArticles();
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

    public function testSort() {
        foreach($this->getArticles() as $article) {
            $this->repo->save($article);
        }
        $articles = $this->repo->loadBySort('article', 'title');
        $this->assertEquals(1, $articles[2]->id);
        $this->assertEquals(2, $articles[1]->id);
        $this->assertEquals(3, $articles[0]->id);
        $articles = $this->repo->loadBySort('article', 'title', 'asc');
        $this->assertEquals(3, $articles[2]->id);
        $this->assertEquals(2, $articles[1]->id);
        $this->assertEquals(1, $articles[0]->id);
    }

    public function testLoad() {
        $user1 = $this->repo->create('user', array('handle' => 'anon1'));
        $user1->articles = array(
            $this->repo->create('article', array(
                'url' => '/test-article-1',
                'article' => 'test 1'
            )),
            $this->repo->create('article', array(
                'url' => '/test-article-2',
                'article' => 'test 2'
            )),
        );

        $user1->favorite_article = $this->repo->create('article', array('url' => '/test-article-3'));

        $this->repo->save($user1);

        $user2 = $this->repo->create('user', array('handle' => 'anon2'));

        $this->repo->save($user2);

        $users = $this->repo->load('user', 0, 1);
        $this->assertEquals(2, sizeof($users));
        $this->assertEquals($user1, $users[0]);
        $this->assertEquals($user2, $users[1]);
    }

    /**
     * @expectedException Rstore\Exception\InvalidIdentifier
     */
    public function testGetModelFromIdentifier() {
        $this->assertEquals(null, $this->repo->loadByIndex('foo', 'id', '0'));
        $method = new ReflectionMethod($this->repo, 'getModelFromIdentifier');
        $method->setAccessible(true);
        $method->invokeArgs($this->repo, array('not_valid_identifier'));
    }
}
