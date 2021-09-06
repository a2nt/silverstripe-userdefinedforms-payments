<?php


namespace A2nt\UserFormsPayments\Extensions;

use A2nt\UserFormsPayments\Controllers\UserFormsPaymentController;
use DNADesign\ElementalUserForms\Model\ElementForm;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\UserForms\Model\EditableFormField\EditableNumericField;

class UserFormPaymentsForm extends DataExtension
{
    private static $db = [
        'OrderID' => 'Varchar',
        'Amount' => 'Currency',
        'PaymentStatus' => 'Enum("Not Required,Unpaid,Paid","Not Required")',
    ];

    private function collectData()
    {
    	$obj = $this->owner;
    	$vals = $obj->Values();

        // collect data
        $data = [];
        foreach ($vals as $valField) {
            $data[$valField->Name] = $valField->Value;
        }

        return $data;
    }

    public function updateAfterProcess()
    {
        $obj = $this->owner;
        $data = $this->collectData();

        // calculate sum
        /* @var ElementForm $userForm */
        $userForm = $obj->Parent();
        $paymentRules = $userForm->PaymentRules();

        $once = ($userForm->PaymentRulesCondition === 'Or') ? true : false;
        $amount = 0;
        foreach ($paymentRules as $rule) {
            $field = $rule->ConditionField();

            if ($field->ClassName === EditableNumericField::class
                && $rule->ConditionOption === 'Summarize'
            ) {
                $amount += $data[$field->Name];
            } else if ($rule->matches($data)) {
                $amount += $rule->Amount;
            }

            if($once && $amount > 0) {
				break;
			}
        }

        if ($amount <= 0 || $userForm->PaymentRulesCondition === 'Never') {
            $obj->PaymentStatus = 'Not Required';
            $obj->Amount = 0;
        } else {
            $obj->PaymentStatus = 'Unpaid';
            $obj->Amount = $amount;
        }

        if ($obj->Amount > 0) {
	        $obj->OrderID = 'O-' . $obj->ID . '-' . strtoupper(substr(uniqid('', true), 0, 4));
	        $obj->write();

	        $link = singleton(UserFormsPaymentController::class)->Link('/pay/SubmittedForm/' . $obj->ID);

	        // break processing at UserDefinedFormController::process($data, $form)
	        $response = HTTPResponse::create()->redirect($link);
	        $response->output();
	        exit();
        }

        // continue processing at UserDefinedFormController::process($data, $form)
        return;
    }

    public function getPaymentItems()
    {
    	$obj = $this->owner;
    	$data = $this->collectData();

    	 /* @var ElementForm $userForm */
        $userForm = $obj->Parent();
        $paymentRules = $userForm->PaymentRules();

        $items = [];
        $once = ($userForm->PaymentRulesCondition === 'Or') ? true : false;
        
        $totalAmount = 0;
        foreach ($paymentRules as $rule) {
        	/* @var EditableFormField $field */
            $field = $rule->ConditionField();

            if (
            	$field->ClassName === EditableNumericField::class
                && $rule->ConditionOption === 'Summarize'
            ) {
                $amount = $data[$field->Name];
                $items[] = [
	                'name' => $field->Title,
		            'price' => $amount,
		            'quantity' => 1,
	            ];
                $totalAmount += $data[$field->Name];
            } else if ($rule->matches($data)) {
                $amount = $rule->Amount;
                $items[] = [
	                'name' => $field->Title,
		            'price' => $amount,
		            'quantity' => 1,
	            ];
                $totalAmount += $rule->Amount;
            }

			if($once && $totalAmount > 0) {
				break;
			}
        }

        return $items;
    }

    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        $readOnlyFields = ['OrderID', 'Amount', 'PaymentStatus'];

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
