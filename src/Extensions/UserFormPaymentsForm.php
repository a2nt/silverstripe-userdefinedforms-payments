<?php


namespace A2nt\UserFormsPayments\Extensions;

use A2nt\UserFormsPayments\Controllers\UserFormsPaymentController;
use DNADesign\ElementalUserForms\Model\ElementForm;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\UserForms\Model\EditableFormField\EditableNumericField;

class UserFormPaymentsForm extends DataExtension
{
    private static $db = [
        'OrderID' => 'Varchar',
        'Amount' => 'Currency',
        'PaymentStatus' => 'Enum("Not Required,Unpaid,Paid","Not Required")',
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
        /* @var ElementForm $userForm */
        $userForm = $obj->Parent();
        $paymentRules = $userForm->PaymentRules();

        $amount = 0;
        foreach ($paymentRules as $rule) {
            $field = $rule->ConditionField();
            if ($field->ClassName === EditableNumericField::class
                && $rule->ConditionOption === 'Summarize'
            ) {
                $amount += $data[$field->Name];
            } else if ($rule->matches($data)) {
                $amount += $rule->Amount;
                
                if ($userForm->PaymentRulesCondition === 'Or') {
                    break;
                }
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
