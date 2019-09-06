# Avatax Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).


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
