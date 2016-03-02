<?php

namespace SilverStripe\Intercom\Task;

interface LeadAndConversationConnector
{
    /**
     * Array of Intercom tags for this connector.
     *
     * @param array $lead
     * @param array $notes
     *
     * @return array
     */
    public function getTags(array $lead, array $notes);

    /**
     * IntercomID value for the team assigned for this connector.
     *
     * @param array $lead
     * @param array $notes
     *
     * @return string
     */
    public function getTeamIdentifier(array $lead, array $notes);

    /**
     * Whether this lead and conversation should be connected to the specified tags and teams.
     *
     * @param array $lead
     * @param array $notes
     *
     * @return bool
     */
    public function shouldConnect(array $lead, array $notes);

    /**
     * The first message to send, from the lead, to Intercom.
     *
     * @param array $lead
     * @param array $notes
     *
     * @return string
     */
    public function getMessage(array $lead, array $notes);
}
