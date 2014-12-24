<?php

class FlexiFormMailChimpHandler extends FlexiFormBasicHandler
{

    private static $handler_label = 'Mailchimp Handler';

    private static $handler_description = 'A basic handler with Mail Chimp List Integration';

    private static $handler_settings = array(
        'MailChimpApiKey' => 'FlexiFormHandlerSetting',
        'MailChimpListID' => 'FlexiFormHandlerSetting',
        'MailChimpEmailField' => 'MailChimpEmailHandlerSetting',
        'MailChimpSendWelcome' => 'FlexiFormBooleanHandlerSetting',
        'MailChimpDoubleOptIn' => 'FlexiFormBooleanHandlerSetting',
        'MailChimpEmailType' => 'FlexiFormEnumHandlerSetting'
    );

    private static $db = array(
        'MailChimpApiKey' => 'Varchar',
        'MailChimpListID' => 'Varchar',
        'MailChimpSendWelcome' => 'Boolean',
        'MailChimpDoubleOptIn' => 'Boolean',
        'MailChimpEmailType' => "Enum(array('html', 'text'))",
        'MailChimpEmailField' => 'Int'
    );

    public function populateDefaults()
    {
        $this->MailChimpDoubleOptIn = true;
        $this->MailChimpSendWelcome = false;
        $this->MailChimpEmailType = 'html';

        return parent::populateDefaults();
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $remove_fields = array(
            'MailChimpEmailField'
        );

        $client = new FlexiFormMailChimpClient($this->MailChimpApiKey);

        foreach ($this->stat('handler_settings') as $component => $class) {
            if (in_array($component, $remove_fields)) {
                $fields->removeByName($component);
                continue;
            }

            $field = $this->augmentMailChimpField($fields->dataFieldByName($component), $component, $client);
            $fields->addFieldToTab('Root.MailChimp', $field);
        }

        return $fields;
    }

    // @TODO management of list subscribers from Mailchimp tab?
    public function updateCMSFlexiTabs(TabSet $fields, TabSet $settings_tab, $flexi)
    {
        parent::updateCMSFlexiTabs($fields, $settings_tab, $flexi);

        $mailchimp_tab = new Tab('MailChimp', 'MailChimp');
        $fields->insertBefore($mailchimp_tab, 'Settings');

        $client = new FlexiFormMailChimpClient($flexi->FlexiFormSetting('MailChimpApiKey')->getValue());

        foreach ($this->stat('handler_settings') as $component => $class) {

            $field_name = $this->getSettingFieldName($component);
            $field = $this->augmentMailChimpField($settings_tab->fieldByName($field_name), $component, $client);

            // Move Settings to designated MailChimp Tab
            $settings_tab->removeByName($field_name);
            $mailchimp_tab->push($field);
        }

        // integrate list groups
        if ($list_id = $flexi->FlexiFormSetting('MailChimpListID')->getValue()) {

            $field = new CheckboxSetField('FlexiFormMailChimpGroups', 'List Groups');

            if ($list_groups = $this->getInterestGroups($list_id, $client)) {
                $field->setSource($list_groups->map('id', 'name'));
                $field->setValue(
                    $flexi->FlexiFormFields()
                        ->filter('ClassName', 'FlexiMailChimpInterestGroupField')
                        ->column('InterestGroupID'));
                $field->description = 'Checked groups are added to your form Fields. Groups are refreshed every 10 minutes.';
            } else {
                $field = $field->performReadonlyTransformation();
                $field->setValue('This list has no Interest Groups');
            }

            $mailchimp_tab->push($field);
        }

        $mailchimp_tab->push(
            new LiteralField('MailChimpRefresh',
                '<br /><hr />NOTE: list and group selections are cached from mailchimp for up to 10 minutes. changing the API Key or list will cause a refresh.'));
    }

    public function onConfigUpdate(FlexiFormConfig $config, DataObject $flexi)
    {
        $fields = $flexi->FlexiFormFields();
        $requested_groups = (array) Controller::curr()->getRequest()->requestVar('FlexiFormMailChimpGroups');
        $current_groups = array();

        // remove non requested groups from field list
        foreach ($fields as $field) {
            if ($field->is_a('FlexiMailChimpInterestGroupField')) {
                if (in_array($field->InterestGroupID, $requested_groups)) {
                    $current_groups[] = $field->InterestGroupID;
                } else {
                    $fields->remove($field);
                    $field->delete();
                }
            }
        }

        // calculate groups that need to be added to field list
        $new_groups = array_diff($requested_groups, $current_groups);
        if (! empty($new_groups)) {

            $client = new FlexiFormMailChimpClient($flexi->FlexiFormSetting('MailChimpApiKey')->getValue());
            $list_id = $flexi->FlexiFormSetting('MailChimpListID')->getValue();
            $groups = $this->getInterestGroups($list_id, $client);

            foreach ($new_groups as $group_id) {
                if ($group = $groups->find('id', $group_id)) {

                    $options = array();
                    foreach ($group['groups'] as $option) {
                        $options[$option['id']] = $option['name'];
                    }

                    $field = FlexiFormUtil::CreateFlexiField('FlexiMailChimpInterestGroupField',
                        array(
                            'Name' => $group['name'],
                            'InterestGroupID' => $group['id'],
                            'InterestGroupFormField' => $group['form_field'],
                            'Readonly' => true,
                            'Options' => $options
                        ));

                    $fields->add($field, array(
                        'Prompt' => $group['name']
                    ));
                }
            }
        }

        return parent::onConfigUpdate($config, $flexi);
    }

    protected function augmentMailChimpField(FormField $field, String $component,
        FlexiFormMailChimpClient $client)
    {
        switch ($component) {
            case 'MailChimpSendWelcome':
                $field->setTitle('Send Welcome Email');
                $field->description = 'flag to control whether the Welcome Email is sent. Has no effect if double opt-in is enabled.';
                break;
            case 'MailChimpDoubleOptIn':
                $field->setTitle('Require Double Opt-In');
                $field->description = 'flag to control whether a double opt-in confirmation message is sent, defaults to true. Abusing this may cause your account to be suspended.';
                break;
            case 'MailChimpEmailField':
                $field->setTitle('Subscription Field');
                $field->description = 'Used as the subscriber email. Must be an Email Field or subclass.';
                break;
            case 'MailChimpEmailType':

                // @TODO ought to let user select preference through a form field [ similar to interest groups? ]
                $field->setTitle('Email Preference');
                $field->description = 'email type preference for subscribers (html or text - defaults to html)';
                break;

            case 'MailChimpApiKey':
                if ($client->isApiKeyValid()) {
                    $field->description = 'This API Key is Valid.';
                } else {
                    if ($client->getApiKey() == '') {
                        $field->description = 'Your MailChimp API Key. Found under Account Extras > Your API Keys';
                    } else {
                        $field->description = 'This API Key is not Valid.';
                    }
                }

                $field->setTitle('MailChimp API Key');
                break;

            case 'MailChimpListID':

                if ($lists = $client->getLists(
                    array(
                        'limit' => 100,
                        'sort_field' => 'web'
                    ))) {

                    $value = $field->Value();
                    $source = array(
                        '' => 'Please Select a List'
                    );
                    $field = new DropdownField($field->getName());

                    $field->description = 'Subscribers will be added to this list. Lists are refreshed every 10 minutes.';

                    if ($lists['total'] > 0) {
                        foreach ($lists['data'] as $list) {
                            $source[$list['id']] = $list['name'];
                        }
                    }

                    $field->setValue($value);
                    $field->setSource($source);
                } else {
                    $field = $field->performReadonlyTransformation();

                    if (! $client->isApiKeyValid()) {
                        $field->setValue('Invalid API Key');
                    } else {
                        $field->setValue('Error loading Lists from your Account');
                    }
                }

                $field->setTitle('MailChimp List ID');
                break;
        }

        return $field;
    }

    protected function getInterestGroups(Integer $list_id, FlexiFormMailChimpClient $client)
    {
        if ($groups = $client->getInterestGroupings(array(
            'id' => $list_id
        ))) {
            return new ArrayList($groups);
        }
    }

    // Submission Handling
    //////////////////////
    public function onSubmit(Array $data, FlexiForm $form, SS_HTTPRequest $request, DataObject $flexi)
    {
        if (parent::onSubmit($data, $form, $request, $flexi)) {
            // @TODO asynchronous api calls


            return true;
        }
    }
}