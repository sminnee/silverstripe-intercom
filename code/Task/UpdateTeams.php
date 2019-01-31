<?php

namespace SilverStripe\Intercom\Task;

use SilverStripe\Dev\BuildTask;
use Guzzle\Service\Resource\Model;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Intercom\Intercom;
use SilverStripe\Intercom\Model\Team;
use SilverStripe\Control\HTTPRequest;

class UpdateTeams extends BuildTask
{
    /**
     * @inheritdoc
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $client = Injector::inst()->get(Intercom::class);

        /** @var Model $response */
        $response = $client->getClient()->getAdmins();
        $admins = $response->getPath("admins");
        $ids = [];

        foreach ($admins as $admin) {
            $ids[] = $admin["id"];

            $existing = Team::get()->filter("IntercomID", $admin["id"])->first();

            if (!$existing) {
                $this->line("- creating record for " . $admin["name"]);

                $existing = Team::create();
                $existing->Email = $admin["email"];
                $existing->Type = $admin["type"];
                $existing->IntercomID = $admin["id"];
            } else {
                $this->line("- updating record for " . $admin["name"]);
            }

            $existing->Name = $admin["name"];
            $existing->write();
        }

        $teams = Team::get();

        foreach ($teams as $team) {
            if (!in_array($team->IntercomID, $ids)) {
                $this->line("- updating record for " . $team->Name);
                $team->delete();
            }
        }
    }

    /**
     * @param string $message
     */
    private function line($message)
    {
        print $message . "\n";
        flush();
    }
}
