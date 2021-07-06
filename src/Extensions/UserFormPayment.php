<?php


namespace A2nt\UserFormsPayments\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;

class UserFormPayment extends DataExtension
{
    private static $has_one = [
        'SubmittedForm' => SubmittedForm::class,
    ];

    public function onCaptured($response)
    {
        die('onCaptured AAAAAAAAAAAAAAAA');
        $order = $this->owner->Order();
        $order->completePayment($this->owner);
    }
}
