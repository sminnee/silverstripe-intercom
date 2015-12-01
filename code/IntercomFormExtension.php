<?php

namespace Sminnee\SilverStripeIntercom;

/**
 * Adds functionality to forms to integrate with Intercom
 *
 * @package  silverstripe/intercom
 * @author  Aaron Carlino <aaron@silverstripe.com>
 */
class IntercomFormExtension extends \DataExtension {

	/**
	 * A map of form field names to Intercom user fields.
	 * [ 
	 * 	'FirstName' => 'name',
	 * 	'EmailAddress' => 'email'
	 * 	]
	 * @var array
	 */
	protected $intercomUserFieldMapping = [];

	/**
	 * A map of form field names to Intercom company fields
	 * [
	 * 	'Company' => 'name'
	 * ]
	 * @var array
	 */
	protected $intercomCompanyFieldMapping = [];
	
	/**
	 * A list of form field names that should be thrown into Intercom notes for the user, mapped
	 * to the labels that should be used in the note
	 * [
	 *    'FavouriteColor' => "The user's favourite color"
	 * ]
	 * @var array
	 */
	protected $intercomNoteMapping = [];

	/**
	 * The heading for the note, e.g. "This lead was submitted on date('d-m-y')"
	 * @var string
	 */
	protected $intercomNoteHeader = '';

	/**
	 * Sets the user field mapping
	 * @param array
	 * @return  Form
	 */
	public function setIntercomUserFieldMapping ($fields) {
		$this->intercomUserFieldMapping = $fields;

		return $this->owner;
	}

	/**
	 * Sets the company field mapping
	 * @param array
	 * @return  Form
	 */
	public function setIntercomCompanyFieldMapping ($fields) {
		$this->intercomCompanyFieldMapping = $fields;

		return $this->owner;
	}

	/**
	 * Sets the note field mapping
	 * @param array
	 * @return  Form
	 */
	public function setIntercomNoteMapping ($noteFields) {
		$this->intercomNoteMapping = $noteFields;

		return $this->owner;
	}

	/**
	 * Sets the note header
	 * @param string
	 * @return  Form
	 */
	public function setIntercomNoteHeader ($header) {
		$this->intercomNoteHeader = $header;

		return $this->owner;
	}

	/**
	 * Sends the form data to Intercom, using the defined mappings
	 */
	public function sendToIntercom () {
		if(
			empty($this->intercomFieldMapping) && 
			empty($this->intercomNoteMapping) &&
			empty($this->intercomCompanyFieldMapping)
		) {
			throw new \LogicException('You must define mapped fields to send a form submission to intercom, using Form::setIntercomFieldMapping() or Form::setIntercomNoteMapping()');
		}

		$intercom = \Injector::inst()->get('Sminnee\SilverStripeIntercom\Intercom');
		$userData = [];

		foreach($this->intercomUserFieldMapping as $formField => $intercomField) {
			$userData[$intercomField] = $this->owner->Fields()->dataFieldByName($formField)->dataValue();
		}

		if(!empty($this->intercomCompanyFieldMapping)) {			
			$companyData = [];
			foreach($this->intercomCompanyFieldMapping as $formField => $intercomField) {
				$companyData[$intercomField] = $this->owner->Fields()->dataFieldByName($formField)->dataValue();
			}
			if(!isset($companyData['company_id'])) {
				$companyData['company_id'] = time();
			}

			$userData['companies'] = [$companyData];
		}

		try {
			$user = $intercom->getClient()->createUser($userData);

			if(!empty($this->intercomNoteMapping)) {
				$noteData = $this->noteHeader;
				$noteData .= '<ul>';
				foreach($this->intercomNoteMapping as $fieldName => $label) {
					$noteData .= sprintf(
						'<li>%s: %s</li>',
						$label,
						$this->owner->Fields()->dataFieldByName($fieldName)->dataValue()
					);
				}
				$noteData .= '</ul>';

				try {
					$intercom->getClient()->createNote(array(
						'body' => $noteData,
						'user' => ['id' => $user['id']]
					));					
				}
				catch (\Exception $e) {					
					SS_Log::log("Could not create note: {$e->getMessage()}", SS_Log::WARN);
				}
			}
		}
		catch (\Exception $e) {
			SS_Log::log("Could not create user: {$e->getMessage()}", SS_Log::WARN);
		}
	}
}