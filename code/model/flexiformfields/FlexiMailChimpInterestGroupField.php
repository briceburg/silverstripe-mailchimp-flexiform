<?php

class FlexiMailChimpInterestGroupField extends FlexiFormOptionField
{

    private static $field_label = 'MailChimp Group Selection Field';

    private static $field_description = 'Integrate with Interest Groups belonging to a MailChimp List';

    protected $allowDuplicateNames = true;

    private static $db = array(
        'InterestGroupID' => 'Varchar', // mailchimp interest group id
        'InterestGroupFormField' => 'Varchar' // field type: checkboxes, radio, select
    );

    // @TODO set options
}