<?php

namespace Sminnee\SilverStripeIntercom;

use SS_Log;
use DataExtension;
use LogicException;
use Injector;
use Exception;

/**
 * Adds functionality to forms to integrate with Intercom
 *
 * @package  silverstripe/intercom
 * @author  Aaron Carlino <aaron@silverstripe.com>
 */
class IntercomFormExtension extends DataExtension {

	/**
	 * A map of form field names to Intercom lead fields.
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
	 * Adds mapping of form fields to intercom user fields
	 * @param array
	 * @return  Form
	 */
	public function addIntercomUserFieldMapping ($fields) {
		$this->intercomUserFieldMapping = array_merge(
			$this->intercomUserFieldMapping,
			$fields
		);

		return $this->owner;
	}

	/**
	 * Removes a form field to Intercom user field mapping
	 * @param  string $key
	 * @return Form
	 */
	public function removeIntercomUserFieldMapping ($key) {
		unset($this->intercomUserFieldMapping[$key]);

		return $this->owner;
	}

	/**
	 * Adds mapping of form fields to intercom company fields
	 * @param array
	 * @return  Form
	 */
	public function addIntercomCompanyFieldMapping ($fields) {
		$this->intercomCompanyFieldMapping = array_merge(
			$this->intercomCompanyFieldMapping,
			$fields
		);

		return $this->owner;
	}

	/**
	 * Removes a form field to Intercom company field mapping
	 * @param  string $key
	 * @return Form
	 */
	public function removeIntercomCompanyFieldMapping ($key) {
		unset($this->intercomCompanyFieldMapping[$key]);

		return $this->owner;
	}

	/**
	 * Adds mapping of note fields
	 * @param array
	 * @return  Form
	 */
	public function addIntercomNoteMapping ($noteFields) {
		$this->intercomNoteMapping = array_merge(
			$this->intercomNoteMapping,
			$noteFields
		);

		return $this->owner;
	}

	/**
	 * Removes a form field to Intercom company field mapping
	 * @param  string $key
	 * @return Form
	 */
	public function removeIntercomNoteFieldMapping ($key) {
		unset($this->intercomNoteMapping[$key]);

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
	 * Adds FormFieldName => IntercomName mappings to a given array.
	 *
	 * To map to a custom attribute, use $my_custom_attribute
	 * 	
	 * @param string $formField     The name of the form field
	 * @param string $intercomField The name of the intercom field it maps to
	 * @param array $data           The array of mappings to update
	 */
	protected function addMappings ($formField, $intercomField, $data) {		
		$val = $this->owner->Fields()->dataFieldByName($formField)->dataValue();
		if($intercomField[0] === '$') {
			if(!isset($data['custom_attributes'])) $data['custom_attributes'] = [];
			$data['custom_attributes'][substr($intercomField, 1)] = $val;
		}
		else {
			$data[$intercomField] = $val;
		}

		return $data;
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
			throw new LogicException('You must define mapped fields to send a form submission to intercom, using Form::setIntercomFieldMapping() or Form::setIntercomNoteMapping()');
		}

		$intercom = Injector::inst()->get('Sminnee\SilverStripeIntercom\Intercom');
		$leadData = [];

		foreach($this->intercomUserFieldMapping as $formField => $intercomField) {
			$leadData = $this->addMappings($formField, $intercomField, $leadData);			
		}

		if(!empty($this->intercomCompanyFieldMapping)) {			
			$companyData = [];
			foreach($this->intercomCompanyFieldMapping as $formField => $intercomField) {
				$companyData = $this->addMappings($formField, $intercomField, $companyData);				
			}
			if(!isset($companyData['company_id'])) {
				$companyData['company_id'] = time();
			}

			$leadData['companies'] = [$companyData];
		}

		try {
			$this->owner->invokeWithExtensions('beforeSendToIntercom', $leadData);
			$lead = $intercom->getClient()->createContact($leadData);

			if(!empty($this->intercomNoteMapping)) {
				$noteData = $this->intercomNoteHeader;
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
					$intercom->getClient()->notes->create([
						'body' => $noteData,
						'user' => ['id' => $lead['id']]
					]);
				}
				catch (Exception $e) {
					SS_Log::log("Could not create note: {$e->getMessage()}", SS_Log::WARN);
				}

				$this->owner->invokeWithExtensions('afterSendToIntercom', $leadData);
			}
		}
		catch (Exception $e) {
			SS_Log::log("Could not create user: {$e->getMessage()}", SS_Log::WARN);
		}
	}
}