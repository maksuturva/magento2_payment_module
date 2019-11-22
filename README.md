# Svea Payment module for Magento 2 (former Maksuturva)

Contributors: maksuturva  
Tags: maksuturva, payment gateway  
Requires magento version at least: 2.x  
Tested up to: 2.3.3  

# System requirements
* Magento 2.1.2 - 2.3.3
* PHP 7.2
* PHP cURL support
* PHP libxml support

There is no guarantee that the module is fully functional in any other environment which does not fulfill the requirements.

# Features
* sub payments filter
* cronjob to deal with delayed Maksuturva payments
* payment methods selector (dropdown or icons)

# Installation instructions

1. Clone repository
`cd /usr/src`
`git clone https://github.com/maksuturva/magento2_payment_module.git`

2. Install 

Don't run commands as root. Use www user.


Enable maintenance mode (optional)
```
php bin/magento maintenance:enable
```

Copy module source to Magento www directory
```
su www-user
cd /var/www/html
cp -r /usr/src/magento2_payment_module/app/code app/.
```

Enable Svea_Maksuturva* modules (and clean generated static view files)
```
php bin/magento module:enable --clear-static-content Svea_Maksuturva Svea_MaksuturvaBase Svea_MaksuturvaCard Svea_MaksuturvaCod Svea_MaksuturvaGeneric Svea_MaksuturvaInvoice Svea_MaksuturvaMasterpass Svea_MaksuturvaPartPayment
```

Run setup:upgrade
```
php bin/magento setup:upgrade
```

Run the setup:di:compile command to generate classes.
```
php bin/magento setup:di:compile
```

Deploy static view files (read devdocs, --force parameter can be used in developer mode)
https://devdocs.magento.com/guides/v2.2/config-guide/cli/config-cli-subcommands-static-view.html#config-cli-subcommands-staticview
```
php bin/magento setup:static-content:deploy
```

Disable maintenace mode (optional)
```
php bin/magento maintenance:disable
```

Flush cache
```
php bin/magento cache:flush
```

# Configuration via Magento Admin

Configurations for the module is found from following locations

General module configuration: `Stores >> Configuration >> Svea >> Maksuturva Payment`

Payment methods' configuration: `Stores >> Configuration >> Sales >> Payment Methods`

## Sandbox mode

If enabled, communication url, seller id and secret key in sandbox fields are used, otherwise personal credentials and communication url are used.

## Seller id and secret key

This parameter provided by Maksuturva. Please note that this key must not be shared with any person, since it allows many operations to be done in your Maksuturva account.

## Communication url

API url to communicate with Maksuturva service. Should be usually kept as is.

In case you want to test using personal test credentials, you must change this to https://test1.maksuturva.fi/. Please note that the url must end with slash `/`.

## Key Version

This parameter provided by Maksuturva. Check your secret key version and input this value into the configuration field.

## Communication encoding

Specifies which encoding is used. Will be deprecated in future, and only UTF8 will be supported. Do not change this.

## Preselect payment method in webshop

Enables selection of Maksuturva payment method directly on Magento checkout, instead of redirecting to Maksuturva service and selecting it there. List of allowed payment methods are fetched from Maksuturva API based on cart total. Certain methods like part payment might be available only when cart total exceed the configured limit.

Please note that this service needs to be enabled by Maksuturva first.

## Preselect form type

Specifies which styling is used on preselection form on checkout. Option to use either basic dropdown or stylished payment icons.
```
Payment fees
```
Only supported when preselect payment method in webshop is enabled. Currently requires module Vaimo_PaymentFee, this might change to more generic way in future versions.

## Delayed capture methods

In case part payment or invoice payment methods are used, this can be used to specify delayed capture for these methods. In normal operation all payments are marked as captured when user returns to webshop from Maksuturva service. When a method is marked as delayed capture method, on return to webshop it will not be marked as captured. In order to capture these, creation of invoice with capture case set to "online" is required.

This is usually used if capture should be done only after shipping the goods to the customer. In case of ERP integration, the integration is responsible of creating the invoice and thus triggering the capture.

Please note that only few methods support delayed capture, these need to be verified from Maksuturva.

The methods are given as comma separated list, example:
```
code1,code2,code3
```
## Query Maksuturva API for orders missing payments (deprecated)

If enabled, will enable cronjob that queries Maksuturva API for order missing payment. This kind of orders might occasionally happen, if after successful payment customer does not return to webshop.

Deprecated since 2.2.0 and should be disabled. Alternative and **better way is to ask Maksuturva to enabled "status OK" callback** to Magento.

## Enable cancellation of settled payments

Allow cancellation of payments that have been settled to seller. This will be attempted if payment is already settled and therefore cannot be refunded normally. These require the refund amount to be paid back to Maksuturva, which will then refund the end customer.

## Send refund payment information with email

Send email containing information for paying back the settled amount of payment to Maksuturva. You can give email sender, recipients, and custom email template.

# Sandbox testing

Most simple way to test the payment module is to switch the Sandbox / Testing mode on. In the sandbox mode after confirming the order, the user is directed to a test page where you can see all the passed information and locate possible errors. In the sandbox page you can also test ok-, error-, cancel- and delayed payment -responses that Maksuturva service might send to your service.

# Testing with a separate test account

For testing the module with actual internet bank, credit card, invoice or part payment services, you can order a test account for yourself.

>https://test1.maksuturva.fi/MerchantSubscriptionBeginning.pmt

When ordering a test account signing the order with your TUPAS bank credentials is not required. When you have completed the order and stored your test account ID and secret key, we kindly ask you to contact us for us to activate the account.

In the test environment no actual money is handled and no orders tracked. For testing the internet bank payments in the test environment we recommend using the test services of either Nordea or Aktia banks bacause in their services the payer credentials are already prefilled or displayed for you. Do not try to use actual bank credentials in the test environment.

For testing our payment service without using actual money, you need to set communication URL in the module configurations as https://test1.maksuturva.fi. All our test environment services are found under that domain unlike our production environment services which are found under SSL-secured domain https://www.maksuturva.fi. Test environment for KauppiasExtranet can be found similarly at https://test1.maksuturva.fi/extranet/.

If sandbox testing passes but testing with test server fails, the reason most likely is in communication URL, seller id or secret key. In that case you should first check that they are correct and no extra spaces are added in the beginning or end of the inputs.


# Known issues

* Module supports Magento gift card, but not 3rd party gift card implementations. If you're using 3rd party modules, you may have to implement support for those by yourself.

# Maksuturva API documentation

API description and documentation can be found at:

>http://docs.maksuturva.fi/

# Support

For general support, please contant tuki@maksuturva.fi.
For technical support, please contact it@maksuturva.fi.
