<?php


namespace A2nt\UserFormsPayments\Extensions;

use A2nt\UserFormsPayments\Models\PaymentConditionRule;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;

class UserFormPayments extends DataExtension
{
    private static $db = [
        'PaymentRulesCondition' => 'Enum("Never,And,Or","Never")',
    ];

    private static $has_many = [
        'PaymentRules' => PaymentConditionRule::class,
    ];

    /**
     * Generate a gridfield config for editing filter rules
     *
     * @return GridFieldConfig
     */
    protected function getRulesConfig()
    {
        $formFields = $this->owner->Fields();

        $config = GridFieldConfig::create()
            ->addComponents(
                new GridFieldButtonRow('before'),
                new GridFieldToolbarHeader(),
                new GridFieldAddNewInlineButton(),
                new GridFieldDeleteAction(),
                $columns = new GridFieldEditableColumns()
            );

        $columns->setDisplayFields(array(
            'ConditionFieldID' => function ($record, $column, $grid) use ($formFields) {
                return DropdownField::create($column, false, $formFields->map('ID', 'Title'));
            },
            'ConditionOption' => function ($record, $column, $grid) {
                $options = PaymentConditionRule::config()->condition_options;
                return DropdownField::create($column, false, $options);
            },
            'ConditionValue' => function ($record, $column, $grid) {
                return TextField::create($column);
            },
            'Amount' => function ($record, $column, $grid) {
                return CurrencyField::create($column);
            },
        ));

        return $config;
    }

    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        $fields->removeByName('PaymentRules');

        $grid = GridField::create(
            'PaymentRules',
            _t(__CLASS__.'.PaymentRules', 'Payment Rules'),
            $this->owner->PaymentRules(),
            $this->getRulesConfig()
        );
        $grid->setDescription(_t(
            __CLASS__ .'.PaymentsDescription',
            'Payment will be required if the custom rules are met. If no rules are defined, '
            . 'payment will not be required.'
        ));

        $fields->addFieldsToTab('Root.PaymentRules', [
            LiteralField::create(
                'PaymentsNote',
                '<div class="alert alert-info">'
                ._t(__CLASS__ .'.PaymentsNote', 'Add conditional logic to require payment')
                .'</div>'
            ),
            DropdownField::create(
                'PaymentRulesCondition',
                _t(__CLASS__.'.RequireCondition', 'Require Condition'),
                [
                    'Never' => _t(__CLASS__ .'.RequireIfNever', 'Never'),
                    'Or' => _t(
                        'SilverStripe\\UserForms\\Model\\UserDefinedForm.SENDIFOR',
                        'Any conditions are true'
                    ),
                    'And' => _t(
                        'SilverStripe\\UserForms\\Model\\UserDefinedForm.SENDIFAND',
                        'All conditions are true'
                    )
                ]
            ),
            $grid
        ]);

        $fields
            ->fieldByName('Root.PaymentRules')
            ->setTitle(_t(__CLASS__ . '.PaymentsTab', 'Payment Rules'));
    }
}
