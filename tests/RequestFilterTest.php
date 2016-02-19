<?php

namespace SilverStripe\Addon\Intercom\Tests;

use DataModel;
use DBField;
use SapphireTest;
use Session;
use SilverStripe\Intercom\ScriptTagsRequestFilter;
use SS_HTTPRequest;
use SS_HTTPResponse;

class RequestFilterTest extends SapphireTest
{
    /**
     * @dataProvider sampleResponses
     *
     * @param SS_HTTPResponse $response
     * @param bool $shouldMatch
     */
    public function testScriptInsertion($response, $shouldMatch)
    {
        $tag = DBField::create_field("HTMLText", "<script>test;</script>");

        if ($shouldMatch) {
            $this->assertRegExp(
                "#<script>test;</script></body>#is",
                $this->checkFilterForResponse($response, $tag)->getBody()
            );
        } else {
            $this->assertNotRegExp(
                "#<script>test;</script></body>#is",
                $this->checkFilterForResponse($response, $tag)->getBody()
            );
        }
    }

    /**
     * @return array
     */
    public function sampleResponses()
    {
        $test = [];

        $test[] = [
            new SS_HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>"), true
        ];

        $test[] = [
            new SS_HTTPResponse("<p>fragment doesn't have script added</p>"), false
        ];

        $response = new SS_HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>");
        $response->addHeader("Content-Type", "text/plain");

        $test[] = [
            $response, 0
        ];

        return $test;
    }

    public function testScriptTagsDisabling()
    {
        $response = new SS_HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>");

        $tag = DBField::create_field("HTMLText", "");

        $this->assertRegExp(
            "#</p></body>#i",
            $this->checkFilterForResponse($response, $tag)->getBody()
        );
    }

    /**
     * @param SS_HTTPResponse $response
     * @param DBField $tag
     *
     * @return SS_HTTPResponse
     */
    public function checkFilterForResponse(SS_HTTPResponse $response, DBField $tag)
    {
        $request = new SS_HTTPRequest("GET", "/");
        $model = new DataModel();
        $session = new Session([]);

        $filter = new ScriptTagsRequestFilter();
        $filter->setTagProvider($tag);

        $filter->preRequest($request, $session, $model);
        $filter->postRequest($request, $response, $model);

        return $response;
    }
}
