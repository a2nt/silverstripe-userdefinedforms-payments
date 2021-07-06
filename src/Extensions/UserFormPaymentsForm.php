<?php


namespace A2nt\UserFormsPayments\Extensions;

use DNADesign\ElementalUserForms\Model\ElementForm;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

class UserFormPaymentsForm extends DataExtension
{
	private static $db = [
		'OrderID' => 'Varchar',
		'Amount' => 'Currency',
		'Status' => 'Enum("Unpaid,Paid")',
	];

	public function updateAfterProcess()
	{
		$obj = $this->owner;
		$vals = $obj->Values();
		// collect data
		$data = [];
		foreach ($vals as $valField) {
			$data[$valField->Name] = $valField->Value;
		}

		// calculate sum
		$paymentRules = $obj->Parent()->PaymentRules();
		$amount = 0;
		foreach ($paymentRules as $rule) {
			if($rule->matches($data)){
				$amount += $rule->Amount;
			}
		}

		$obj->Amount = $amount;
		$obj->OrderID = 'O-'.$obj->ID.'-'.strtoupper(substr(uniqid('',true),0,4));
		$obj->write();
	}

	public function updateCMSFields(FieldList $fields)
	{
		parent::updateCMSFields($fields);

		$readOnlyFields = ['OrderID', 'Amount', 'Status'];

		foreach ($readOnlyFields as $key) {
			$fields
				->dataFieldByName($key)
				->setReadonly(true);
		}

		/*$fields->addFieldToTab(
			'Root.Main',
			NumericField::create('TotalPaid')
				->setValue($this->owner->TotalPaid())
				->setReadonly(true),
			'Amount'
		);*/
	}
}