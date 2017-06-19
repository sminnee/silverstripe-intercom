<?php
namespace Sminnee\SilverStripeIntercom\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\LazyOpenStream;
use Sminnee\SilverStripeIntercom\Intercom;
use Intercom\IntercomClient;
use SapphireTest;
use Injector;
use IntercomCleanupTask;

class IntercomCleanupTaskTest extends SapphireTest {

	/**
	 * @var string
	 */
	protected static $fixture_file = 'IntercomCleanupTaskTest.yml';

	/**
	 * @var array
	 */
	protected $history;

	public function setUp() {
		// setup mocking of guzzle client
		$gateway = new Intercom();
		$this->history = [];
		$handler = HandlerStack::create();
		$handler->push(Middleware::history($this->history));
		$client = new IntercomClient(null, null);
		$client->setClient(new Client(['handler' => $handler]));
		$gateway->setClient($client);
		Injector::inst()->registerService($gateway, 'Sminnee\SilverStripeIntercom\Intercom');
		parent::setUp();
	}

	/**
	 * @param array $fixtures
	 */
	protected function setMockResponses(array $fixtures) {
		$responses = [];
		foreach($fixtures as $fixture => $statusCode) {
			$body = new LazyOpenStream(__DIR__ . '/fixtures/' . $fixture, 'r');
			$responses[] = new Response($statusCode, [], $body);
		}
		$gateway = new Intercom();
		$mock = new MockHandler($responses);
		$handler = HandlerStack::create($mock);
		$handler->push(Middleware::history($this->history));
		$client = new IntercomClient(null, null);
		$client->setClient(new Client(['handler' => $handler]));
		$gateway->setClient($client);
		Injector::inst()->registerService($gateway, 'Sminnee\SilverStripeIntercom\Intercom');
	}

	public function testDeletesUserNotExistingLocally() {
		$this->setMockResponses([
			'get_users_page1.json' => 200,
			'get_users_page2.json' => 200,
			'delete_users.json' => 200
		]);
		IntercomCleanupTask::create()->run(null);
		$this->assertCount(3, $this->history);

		$req = array_pop($this->history)['request'];
		$this->assertEquals('DELETE', $req->getMethod());
		$this->assertEquals('/users/4', $req->getUri()->getPath());

		$req = array_pop($this->history)['request'];
		$this->assertEquals('GET', $req->getMethod());
		$this->assertEquals('/users', $req->getUri()->getPath());
		$this->assertEquals('page=2', $req->getUri()->getQuery());

		$req = array_pop($this->history)['request'];
		$this->assertEquals('GET', $req->getMethod());
		$this->assertEquals('/users', $req->getUri()->getPath());
		$this->assertEquals('page=1', $req->getUri()->getQuery());
	}

}
