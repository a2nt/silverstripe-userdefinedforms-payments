<?php


namespace A2nt\UserFormsPayments\Admins;


use SilverStripe\Admin\ModelAdmin;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;

class UserFormOrders extends ModelAdmin
{
	private static $managed_models = [
		SubmittedForm::class,
	];

	private static $url_segment = 'formorders';
    private static $menu_title = 'Form Orders';

    public function getList()
    {
        $list =  parent::getList();

        switch ($list->dataClass()){
	        case SubmittedForm::class:
	        	$list->filter(['Amount:GreaterThan' => 0]);
	        	break;
        }

        return $list;
    }
}