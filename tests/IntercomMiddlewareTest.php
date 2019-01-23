<?php

namespace SilverStripe\Intercom\Tests;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Intercom\IntercomMiddleware;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBField;

class IntercomMiddlewareTest extends SapphireTest
{
    /**
     * @dataProvider sampleResponsesProvider
     * @param HTTPResponse $response
     * @param int $match
     */
    public function testScriptInsertion($response, $match)
    {
        // Simulating an enabled script tag
        $tag = DBField::create_field(DBHTMLText::class, '<script>test;</script>');

        // Check that script has been added before the body
        $assertion = ($match) ? 'assertRegExp' : 'assertNotRegExp';
        $this->{$assertion}(
            '/<script>test;<\/script><\/body>/is',
            $this->checkFilterForResponse($response, $tag)->getBody()
        );
    }

    /**
     * @return array[]
     */
    public function sampleResponsesProvider()
    {
        $test = [];

        // Regular responses
        $test[] = [
            new HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>"),
            1
        ];

        // Fragment response without a </body> doesn't have code added
        $test[] = [
            new HTTPResponse("<p>fragment doesn't have script added</p>"),
            0
        ];

        // Plaintext response doesn't have code added
        $response = new HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>");
        $response->addHeader("Content-Type", "text/plain");
        $test[] = [$response, 0];

        return $test;
    }

    /**
     * Test that no script is addded if IntercomScriptTags is disabled
     */
    public function testScriptTagsDisabling()
    {
        $response = new HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>");

        // Empty response, simulating a disabled script tag
        $tag = DBField::create_field(DBHTMLText::class, '');

        // Check that script has been added before the body
        $this->assertRegExp(
            '/<\/p><\/body>/i',
            $this->checkFilterForResponse($response, $tag)->getBody()
        );
    }

    /**
     * Set up test scaffold to check the RequestFilter's effect on a response
     *
     * @param HTTPResponse $response
     * @param ViewableData $tag
     * @return HTTPResponse
     */
    public function checkFilterForResponse($response, $tag)
    {
        // Test stub
        $request = new HTTPRequest("GET", "/");
        $delegate = function ($request) use ($response) {
            return $response;
        };

        // Execute the middleware
        $middleware = new IntercomMiddleware();
        $middleware->setTagProvider($tag);

        return $middleware->process($request, $delegate);
    }
}
