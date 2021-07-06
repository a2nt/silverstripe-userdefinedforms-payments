<?php


namespace A2nt\UserFormsPayments\Extensions;

use DNADesign\ElementalUserForms\Model\ElementForm;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

class UserFormPaymentsForm extends DataExtension
{
	private static $db = [
		'Amount' => 'Currency',
	];

	public function updateAfterProcess()
	{
		$vals = $this->owner->Values();
		// collect data
		$data = [];
		foreach ($vals as $valField) {
			$data[$valField->Name] = $valField->Value;
		}

		// calculate sum
		$paymentRules = $this->owner->Parent()->CustomRules();
		$amount = 0;
		foreach ($paymentRules as $rule) {
			if($rule->matches($data)){
				$amount += $rule->Amount;
			}
		}

		$this->owner->Amount = $amount;
		$this->owner->write();
	}

	public function updateCMSFields(FieldList $fields)
	{
		parent::updateCMSFields($fields);

		$fields->dataFieldByName('Amount')->setReadonly(true);
		/*$fields->addFieldToTab(
			'Root.Main',
			NumericField::create('TotalPaid')
				->setValue($this->owner->TotalPaid())
				->setReadonly(true),
			'Amount'
		);*/
	}
}