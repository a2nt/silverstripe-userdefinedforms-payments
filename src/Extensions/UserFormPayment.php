<?php

namespace A2nt\UserFormsPayments\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\ORM\DataExtension;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;

class UserFormPayment extends DataExtension
{
    private static $has_one = [
        'SubmittedForm' => SubmittedForm::class,
    ];

    public function onCaptured($response)
    {
    	$obj = $this->owner;
    	$form = $obj->SubmittedForm();

    	if($form->Amount === $form->TotalPaidOrAuthorized()) {
    		$form->setField('PaymentStatus', 'Paid');
    		$form->write();
	    }
    }
}
