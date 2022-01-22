# Svea Payments payment module for Magento 2

Contributors: Svea Payments Oy  
Tags: svea, payment gateway  
Requires Magento version at least: 2.3.4  
Tested up to: 2.4.2  

# System requirements
* Magento 2.3.4 - 2.4.2
* PHP >7.2
* PHP cURL support
* PHP libxml support

There is no guarantee that the module is fully functional in any other environment which does not fulfill the requirements. 

# Features

* Dynamic loading for payment method icons
* Sub payments filter
* Cronjob to deal with delayed Svea payments
* Payment methods selector (dropdown or icons)
* Invoice credit memo support (partial and full refund)

Note! The latest and improved Maksuturva Collated payment method is the best suitable option for most users with all the needed features.  

# Installation instructions

1. Clone repository
```
cd /usr/src
git clone https://github.com/maksuturva/magento2_payment_module.git
```

2. Install 

Don't run commands as root. Use www user.
```
su www-data
```

Enable the maintenance mode
```
php bin/magento maintenance:enable
```

Copy the module source to Magento www directory (as www-user)
```
cd /var/www/html
cp -r /usr/src/magento2_payment_module/app/code app/.
```

Enable Svea_Maksuturva* modules (and clean generated static view files).  
  
Minimum set  
```
php bin/magento module:enable --clear-static-content Svea_Maksuturva Svea_MaksuturvaBase Svea_MaksuturvaCollated
```
  
Alternatively, if you want all modules  
```
php bin/magento module:enable --clear-static-content Svea_Maksuturva Svea_MaksuturvaBase Svea_MaksuturvaCollated Svea_OrderComment Svea_MaksuturvaCard Svea_MaksuturvaGeneric Svea_MaksuturvaInvoice Svea_MaksuturvaPartPayment
```

Run setup:upgrade
```
php bin/magento setup:upgrade
```

Run the setup:di:compile command to generate classes.
```
php bin/magento setup:di:compile
```

Deploy static view files
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

# Upgrade previous version

Upgrading process is similar to the installation and database should be upgraded automatically in the process. Take a backup from your database before upgrading.

First, move your old payment module version to the safe place and after that, follow the section Installation Instructions 

```
su www-data
mv /var/www/html/app/code/Svea /var/www/html/app/code/Svea.old
```

# Logging

If you suspect that the payment module is not working properly, please see log files for possible errors and information. The log file is named `svea-payment-module.log` and it's located at Magento log directory. For example, in the directory `/var/www/html/var/log`.

Debug (DEBUG), info and (INFO) error (ERR) log levels are used for the logging.

 
# Configuration via Magento Admin

Configurations for the module is found from following locations

General module configuration: `Stores >> Configuration >> Svea Payments >> Svea Payments Configuration`

Payment methods' configuration: `Stores >> Configuration >> Sales >> Payment Methods`

Order comment configuration: `Stores >> Configuration >> Sales >> Sales >> Order Comment`

For minimum setup, you could enable only the `Maksuturva Collated` payment method. See Basic Settings section.

## Sandbox mode

If enabled, endpoint API url, seller id and secret key in sandbox fields are used, otherwise personal credentials and endpoint API url are used.

## Seller id and secret key

This parameter provided by Svea Payments. Please note that this key must not be shared with any person, since it allows many operations to be done in your Svea Payments account.

## Endpoint API url

The endpoint url to communicate with Svea Payments service API. Should be usually kept as is.

In case you want to test using personal test credentials, you must change this to https://test1.maksuturva.fi/. Please note that the url must end with slash `/`.

## Key Version

This parameter provided by Svea Payments. Check your secret key version and input this value into the configuration field.

## Communication encoding

Specifies which encoding is used. Only UTF8 will be supported. Do not change this.

## Preselect payment method in webshop

Enables selection of Svea Payments payment method directly on Magento checkout, instead of redirecting to  Svea Payments service and selecting it there. List of allowed payment methods are fetched from  Svea Payments API based on cart total. Certain methods like part payment might be available only when cart total exceed the configured limit.

Please note that this service needs to be enabled by  Svea Payments first.

## Preselect form type

Specifies which styling is used on preselection form on checkout. Option to use either basic dropdown or stylished payment icons.

## Payment fees

Only supported when preselect payment method in webshop is enabled. 

The payment module has support for defining handling fees for each submethod under each payment method, including Collated payment method. For example, with "10;FI01=5;FI06=7.5" the fee in quote gets resolved based on the selected method in the following way:
* FI01 gets a fee of 5   
* FI06 gets a fee of 7.5  
* Other methods in that group get a fee of 10  

## Delayed capture methods

In case part payment or invoice payment methods are used, this can be used to specify delayed capture for these methods. In normal operation all payments are marked as captured when user returns to webshop from Svea Payments service. When a method is marked as delayed capture method, on return to webshop it will not be marked as captured. In order to capture these, creation of invoice with capture case set to "online" is required.

This is usually used if capture should be done only after shipping the goods to the customer. In case of ERP integration, the integration is responsible of creating the invoice and thus triggering the capture.

Please note that only few methods support delayed capture, these need to be verified from Svea Payments.

The methods are given as comma separated list, example:
```
code1,code2,code3
```

Allowed delivery method codes: http://docs.sveapayments.fi/api/post-payment-api/delivery-information-management/#DeliveryInformationManagement-DeliveryMethodcodes

## Query Svea Payments API payment status for orders automatically (version 1.6.0 above)

If enabled, this will activate a cronjob that queries `Pending` payments from Svea Payments API. This kind of order might occasionally happen, if after successful payment customer does not return to the webshop, but the payment transaction was successful.

Job is `run hourly`, and the status is queried pending orders not older than one day. If you need to check older order statuses, use the manual query on the admin page.

To check that automatic status query is working, see log file for following items. The job is run hourly, so you need wait a while after activation.
```
2021-06-27T17:01:03+00:00 INFO (6): "Finding Pending orders to query between ...."
```

If you are not getting the log items above, check section `Cron Jobs`.


## Enable cancellation of settled payments

Allow cancellation of payments that have been settled to seller. This will be attempted if payment is already settled and therefore cannot be refunded normally. These require the refund amount to be paid back to Svea Payments, which will then refund the end customer.

## Send refund payment information with email

Send email containing information for paying back the settled amount of payment to Svea Payments. You can give email sender, recipients, and custom email template.

# Maksuturva Collated

To use this feature, check that you have installed Svea_MaksuturvaCollated module.  

## Settings required to display payment methods at the checkout

* make sure the currency is set to EURO (`Stores >> Configuration >> General >> Currency Setup >> Base Currency`),

* make sure the "Preselect payment method in webshop" in Svea settings is enabled (`Stores >> Configurations >> Svea >> Maksuturva Payment >> Preselect payment method in webshop`)

* disable other Maksuturva payment methods,

* use default country as Finland (`Stores >> Configurations >> General >>> General >> Default Country`) - recomended,
 

## Basic settings

Main level of configuration for this "grouped" view is under "Maksuturva Collated" payment method is illustrated below
Settings: `Stores >> Configuration >> Sales >> Payment Methods >> Maksuturva Collated`

![image](https://user-images.githubusercontent.com/41151878/114890710-8ea5ee00-9e0b-11eb-9791-10cad6fd2bfd.png)

## Settings for "Maksuturva Collated Payment Separation"

You can enable "Enable subpayment division" (above settings), then you can use "Maksuturva Collated Payment Separation" section to group methods per type (Method filter):

![image](https://user-images.githubusercontent.com/41151878/114892275-e5f88e00-9e0c-11eb-9f0a-a4a336ba7f70.png)

Titles are store-specific, so they can be customized for different views and languages. Below are sample settings for each section of the "Payment Methods Filter":

* Pay Later:
```
FI70;FI71;FI72
```
* Pay Now via Mobile etc:
```
FI50;FI51;FI52;FI53;FI54;PIVO;SIIR
```
* Pay Now via Online Banking:
```
FI01;FI02;FI03;FI04;FI05;FI06;FI07;FI08;FI09;FI10;FI11;FI12;FI13;FI14;FI15
```
![image](https://user-images.githubusercontent.com/41151878/114894359-dbd78f00-9e0e-11eb-9cc2-12280b716fe9.png)

See Payment Fee section, how to configure the payment fees if needed.

## Order Comment

To use this feature, check that you have installed Svea_OrderComment module.   

Functionality that allows you to add an optional comment when placing an order. 
Settings: `Stores >> Configuration >> Sales >> Sales >> Order Comment`

To get the optimal placement for the comment field (under the billing address box), the following setting should be set:
`Stores >> Configuration >> Sales >> Checkout Options >> Display Billing Address On: Payment Page`

![image](https://user-images.githubusercontent.com/41151878/114896385-95832f80-9e10-11eb-889c-ffb2c9025734.png)

![image](https://user-images.githubusercontent.com/41151878/114896633-cbc0af00-9e10-11eb-98ff-6af557e32d95.png)

## Terms

Configuration options for separate terms can be found under `Stores >> Configuration >> Sales >> Svea Maksuturva Payment >> Maksuturva Terms`

![image](https://user-images.githubusercontent.com/41151878/114896988-1c380c80-9e11-11eb-9b6d-bf4c020e29fb.png)

There are two ways the terms can work: 

1. part of the text can be a hyperlink to the terms (automatically created if the `Which part of the text is the link`, field
is set to a value that is within the Terms and Conditions text field),

![image](https://user-images.githubusercontent.com/41151878/114897671-bf892180-9e11-11eb-8c53-db7e04cb1bc5.png)


2. appended to the end of the Terms and Conditions text field value

![image](https://user-images.githubusercontent.com/41151878/114897738-cfa10100-9e11-11eb-8b9e-2519d5a04419.png)


# Sandbox testing

Most simple way to test the payment module is to switch the Sandbox / Testing mode on. In the sandbox mode after confirming the order, the user is directed to a test page where you can see all the passed information and locate possible errors. In the sandbox page you can also test ok-, error-, cancel- and delayed payment -responses that Svea Payments service might send to your service.

# Testing with a separate test account

For testing the module with actual internet bank, credit card, invoice or part payment services, you can order a test account for yourself.

>https://test1.maksuturva.fi/MerchantSubscriptionBeginning.pmt

When ordering a test account signing the order with your TUPAS bank credentials is not required. When you have completed the order and stored your test account ID and secret key, we kindly ask you to contact us for us to activate the account.

In the test environment no actual money is handled and no orders tracked. For testing the internet bank payments in the test environment we recommend using the test services of either Nordea or Aktia banks bacause in their services the payer credentials are already prefilled or displayed for you. Do not try to use actual bank credentials in the test environment.

For testing our payment service without using actual money, you need to set communication URL in the module configurations as https://test1.maksuturva.fi. All our test environment services are found under that domain unlike our production environment services which are found under SSL-secured domain https://www.maksuturva.fi. Test environment for KauppiasExtranet can be found similarly at https://test1.maksuturva.fi/extranet/.

If sandbox testing passes but testing with test server fails, the reason most likely is in endpoint API URL, seller id or secret key. In that case you should first check that they are correct and no extra spaces are added in the beginning or end of the inputs.

# Cron jobs

Magento cron scheduler run commands on system cron scheduler. If your cron jobs are not executed, check that system cronjob is running.

```
ps -ef | grep cron | grep -v grep
```
You should get something like
```
root        513      0  0 13:51 ?        00:00:00 /usr/sbin/cron
```

Next use your www user and check the crontab

```
su www-data
crontab -l
```

You should get Magento cron jobs in the listing. Magento setup:cron:run is the most important for the payment module.  
```
* * * * * www-data /usr/local/bin/php /var/www/html/bin/magento cron:run | grep -v "Ran jobs by schedule" >> /var/www/html/var/log/magento-cron.log  
* * * * * www-data /usr/local/bin/php /var/www/html/bin/magento indexer:reindex
* * * * * www-data /usr/local/bin/php /var/www/html/update/cron.php >> /var/www/html/var/log/update-cron.log
* * * * * www-data /usr/local/bin/php /var/www/html/bin/magento setup:cron:run >> /var/www/html/var/log/setup-cron.log
```
If you need to edit cron jobs, command is `crontab -e`

# Known issues

* Module supports Magento gift card, but not 3rd party gift card implementations. If you're using 3rd party modules, you may have to implement support for those by yourself.

# Svea Payments API documentation

API description and documentation can be found at:

>http://docs.sveapayments.fi/api/

# Partial and full refund

Partial and full refund is supported through credit memo. Creating one can be done from the order's invoice.

Open Invoice / View / Credit Memo and Add a new credit memo with refund. If the total amount matches with the invoice total, full refund is created. Otherwise partial refund. You can create multiple partial refund credit memos.  

# Troubleshooting

* See Logging for log files. The version 1.5.4 and above has more informational logging available.
* If you get `No payment methods available` on payment page, you might check the sellerid, secret key and endpoint API url once more. Try to use sandbox mode.

# Support

For General support, please contant support.payments@svea.fi    
For Technical support, please contact info.payments@svea.fi  
