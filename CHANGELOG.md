# Changelog
All notable changes to this module will be documented in this file.
 
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/). 

## [1.6.1] - 2021-08-12
### Fixed
 - Fixed partial / full refund cancel type logic for the refund api implementation  
 
## [1.6.0] - 2021-06-28
### Changed
 - 'Query Svea Payments API for order status automatically' functionality rewritten
 - Crontab changes
 - Rearranged admin form elements and Communication url renamed to Endpoint API URL

## [1.5.4] - 2021-06-20
### Changed
 - Logging file is renamed to svea-payment-module.log
 - More info logging for status changes
### Fixed
 - Removed conditional order update status to paid state as there was some problems with updating the status

## [1.5.3] - 2021-05-11
### Fixed  
 - Fixed handling fees missing -bug (affected version 1.5.1)
 - Fixed Invoice payment method handling fee  
 
## [1.5.1] - 2021-04-20
### Changed
 - Handling costs row is not added to the payment data if the amount is 0,00
 - Added identifiers and more information to the status query log items
 
## [1.5.0] - 2021-04-17
### Added 
 - Collated payment method view, see README.md / Collated Payment
 - Order comment, see README.md / Order comment
 - Customizable terms for payment, see README.md / Terms

## [1.4.1] - 2021-03-21
### Changed
 - Removed MasterPass payment method
### Fixed
 - Observer PaymentMethodIsActive fixed to check class interface

## [1.4.0] - 2021-03-17
### Added
 - Maksuturva Generic dynamic payment method icons on Payment page
### Changed
 - some Maksuturva -> Svea Payments text changes 

## [1.3.0] - 2020-04-07
### Added
 - Credit memo Refund feature

## [1.2.0] - 2019-11-25
### Changed
 - Piimega -> Svea. Paths changed and README.md installation instructions updated
 - maksu 37 payment status change from hash calc to basic auth

### Fixed
 - GiftCard fixes from pull request Feature/maksu 36 support mgo giftcard
 - Model/ResosurceModel/Method.php check for empty imageurl added

## [1.1.0] - 2019-09-09
### Added
- MINOR Add support for Magento gift card.