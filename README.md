silverstripe-mailchimp-flexiform
================================

Nicely integrate MailChimp lists with SilverStripe FlexiForms. Includes support for Interest Groups & more.


Requirements
------------

SilverStripe FlexiForm https://github.com/briceburg/silverstripe-flexiform

ZfrMailChimp https://github.com/zf-fr/zfr-mailchimp

Tested in SilverStripe 3.1

Screenshots
-----------

![flexiform fields](docs/screenshots/mailchimp_flexiform_1.png?raw=true)

![field editing](docs/screenshots/mailchimp_flexiform_2.png?raw=true)

Installation
------------

```
php composer.phar require briceburg/silverstripe-mailchimp-flexiform
```

Usage 
=====

This module integrates your SilverStripe flexiforms with MailChimp through the MailChimp v2 API. 

You associate a form with a MailChimp List. When a valid submission is made, the 
submitter is subscribed to the associated list and _optionally_ 
added to selected Interest Groups. 

You have control over Welcome Emails, Double Opt-In, and Email Preferences. 

Configuration is **per-form**, allowing you to override the default handler settings. E.g. You may use the same handler on forms which subscribe the user to different lists.


* Install this module and trigger the environment builder (/dev/build). 

* Create a new `Mailchimp Handler` under the _Manage Handlers_ area from any FlexiForm _Settings_ tab.

* Assign the Mailchimp Handler to any form you wish to integrate with MailChimp. Save. The MailChimp tab will appear where you may further configure the integration. 



### Automatic Form Creation

You may programatically create forms integrated with MailChimp using the
convenicences of [flexiform](https://github.com/briceburg/silverstripe-flexiform). 


```yaml

# mysite/_config/config.yml


# Make sure we have an Email Field named `Email`
FlexiFormEmailField:
  required_field_definitions: 
    - Name: Email
      Readonly: true
      

# Make sure we have a MailChimp Handler named `NewsletterHandler`
FlexiFormMailChimpHandler:
  required_handler_definitions:
    - Name: NewsletterHandler
      MailChimpListID: 0ffffff, 
      MailChimpApiKey: 0000000-us9,
      Readonly: true


# Automatically create a Content Block with a MailChimp enabled flexiform 
CommonContentNewsletterBlock:
  required_records:
    - Title: sidebar
      Heading: iCEBURG Labs Newsletter
      Content: iCEBURG Labs is proud to offer an email subscription service...
      Readonly: true
  flexiform_default_handler_name: NewsletterHandler
  flexiform_initial_fields:
    - Name: Email
      Type: FlexiFormEmailField
      Prompt: Email
      Required: true

```


```php

class CommonContentNewsletterBlock extends CommonContentBlock
{

    private static $label = 'Newsletter Block';

    private static $extensions = array(
        'FlexiFormExtension'
    );
    
    ...
}

```


To learn more, read the [flexiform configuration documentation](https://github.com/briceburg/silverstripe-flexiform/blob/master/docs/CONFIGURATION.md).

This example uses the [commoncontent](https://github.com/briceburg/silverstripe-commoncontent) addon.



