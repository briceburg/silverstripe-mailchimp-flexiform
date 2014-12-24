<?php

class MailChimpEmailHandlerSetting extends FlexiFormFieldHandlerSetting {

    // limit selection to email fields
    private static $allowed_field_types = array(
        'FlexiFormEmailField'
    );


}