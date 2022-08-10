# Avatax Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 3.0.1 - 2022-08-10
### Fixed
- Fixed an address validation error related to address model changes. [#49](https://github.com/surprisehighway/craft-avatax/issues/49)
- Fixed an address validation error related to estimated address changes.

## 3.0.0 - 2022-07-18
### Added
- Craft 4 compatibility.

## 2.2.0 - 2022-05-05
### Added
- Updated Avatax SDK to version 22.3.0.

### Fixed
- Fixed Guzzle version conflict. [#44](https://github.com/surprisehighway/craft-avatax/issues/44)

## 2.1.8 - 2021-11-8
### Fixed
- Fixed errors that occur when running from the console. [#42](https://github.com/surprisehighway/craft-avatax/issues/42)

## 2.1.7 - 2021-07-28
### Added
- Added the ability to override the Tax Calculation setting using hidden form inputs. [#40](https://github.com/surprisehighway/craft-avatax/issues/40)

## 2.1.6 - 2021-07-15
### Fixed
- Fixed an issue where after install event breaks automated testing - thanks @davist11. [#37](https://github.com/surprisehighway/craft-avatax/issues/37)

## 2.1.5 - 2021-07-07
### Fixed
- Fixed an error that could occur when logging a tax calculation error. [#35](https://github.com/surprisehighway/craft-avatax/issues/35)
- Fixed an error when using a dropdown for the product tax code field. [#34](https://github.com/surprisehighway/craft-avatax/issues/34)

## 2.1.4 - 202105-21
### Added
- Allow for live and sandbox credentials to be set as ENV variables with autosuggest fields in the plugin settings to keep sensitive info out of Project Config. [#11](https://github.com/surprisehighway/craft-avatax/issues/11) [30](https://github.com/surprisehighway/craft-avatax/issues/30)
- Update config override example to default to multi-environment settings with ENV examples.
- For new installs the address validation setting will be disabled by default.

### Fixed
- Better error handling. No longer throw exceptions on the front end but log more complete error responses. [#29](https://github.com/surprisehighway/craft-avatax/issues/29) [#31](https://github.com/surprisehighway/craft-avatax/issues/31)

## 2.1.3 - 2021-01-28
### Fixed
- Fixed an issue where updating the shipping state did not refresh the cached order. [#27](https://github.com/surprisehighway/craft-avatax/issues/27)

## 2.1.2 - 2021-01-05
### Fixed
- Skip address validation for estimated addresses to avoid errors with incomplete addresses. [#22](https://github.com/surprisehighway/craft-avatax/issues/22)
- Fixed an issue where updating the shipping state did not refresh the cached tax. [#24](https://github.com/surprisehighway/craft-avatax/issues/24)
- Fixed an issue where addresses returned by Avalara as unresolved were still considered valid. [#25](https://github.com/surprisehighway/craft-avatax/issues/25)

## 2.1.1 - 2020-06-08
### Fixed
- Fixed an issue where settings fields were readonly even when not being overridden by config file. [#20](https://github.com/surprisehighway/craft-avatax/issues/20)

## 2.1.0 - 2020-02-20
### Added
- Added Commerce 3 compatibility.
- AvaTax now requires Craft 3.3 and Craft Commerce 2.2 or later, or Craft 3.4 and Craft Commerce 3.0 and later.

## 2.0.8 - 2020-02-18
### Fixed
- Fixed deprecation errors caused by `craft\commerce\elements\Order::getAdjustmentsTotalByType()` - thanks @jmauzyk!
- Fixed asset bundle namespace - thanks @abryrath!
- Fixed potential bug in json address validation if country was not set in address model

## 2.0.7 - 2019-09-20
### Added
- Added a json endpoint to use for ajax CertCapture client lookup on the front end  ([more](https://github.com/surprisehighway/craft-avatax#certcapture-customer-lookup))

## 2.0.6 - 2019-09-11
### Added
- Added a setting to disable sending partial refunds to AvaTax ([more](https://github.com/surprisehighway/craft-avatax#refunds))
- Added a json endpoint to use for ajax address validation on the front end  ([more](https://github.com/surprisehighway/craft-avatax#ajax-address-validation))

### Fixed
- Fixed an issue where additional partial refunds would not be processed after the first because AvaTax requires unique document ids

## 2.0.5 - 2019-09-06
### Added
- Added support for overriding the Customer Code sent to Avalara based on the value of a User or Order field ([more](https://github.com/surprisehighway/craft-avatax#customer-code))

## 2.0.4 - 2019-09-06
### Fixed
- Fixed deprecation error (pull request [#8](https://github.com/surprisehighway/craft-avatax/pull/8))
- Discounts for a specific line item now send the line item tax code instead of the default discount code (pull request [#4](https://github.com/surprisehighway/craft-avatax/pull/4))

## 2.0.3 - 2019-05-03
### Fixed
- Fixed an bug where address validation was being triggered in the control panel when saving a user ([#5](https://github.com/surprisehighway/craft-avatax/issues/5))

## 2.0.2 - 2019-04-22
### Added
- Added control panel icon
- Added Avalara certification badge to README

### Fixed
- Fixed typo in README installation instructions
- Fixed depracation warnings

## 2.0.1 - 2019-02-26
### Added
- Added support for partial refunds

## 2.0.0 - 2019-02-15
### Added
- Initial beta release for Craft 3 and Commerce 2
