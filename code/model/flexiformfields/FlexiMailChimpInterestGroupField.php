<?php

class FlexiMailChimpInterestGroupField extends FlexiFormOptionField
{

    private static $field_label = 'MailChimp Group Selection Field';

    private static $field_description = 'Integrate with Interest Groups belonging to a MailChimp List';

    protected $allowDuplicateNames = true;

    private static $db = array(
        'InterestGroupID' => 'Varchar', // mailchimp interest group id
        'InterestGroupFormField' => 'Varchar'
    );

    public function getFormField($title = null, $value = null, $required = false)
    {
        switch ($this->InterestGroupFormField) {
            case 'checkboxes':
            case 'checkbox':
                $class = 'CheckboxSetField';
                break;

            case 'radio':
            case 'radios':
                $class = 'OptionsetField';
                break;

            case 'dropdown':
            default:
                $class = 'DropdownField';
                break;
        }

        $this->set_stat('field_class',$class);

        return parent::getFormField($title, $value, $required);
    }
}