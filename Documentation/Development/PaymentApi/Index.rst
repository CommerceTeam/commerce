.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Payment API
===========


Related to issue: http://forge.typo3.org/issues/4980


The 'new' payment API gives programmers a basic API to select and create different payment methods under different circumstances.

The basic structure is like this:
- There are multiple payment types (eg. credit card and prepayment).
- A specific payment type can have multiple criteria to determine if this payment type is available in the specific environment (for example if prepayment is available in the customers country). If all criteria return true, the payment type is available and can be selected by the customer.
- A payment type can have multiple payment provider, there can be for example multiple credit card provider. The first available provider will be chosen.
- Like a payment type, a provider can have multiple criteria, if all criteria return true for a specific provider, the provider is selected as payment provider of the chosen payment type.


See the default $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][COMMERCE_EXTKEY]['SYSPRODUCTS']['PAYMENT'] in ext_localconf.php for example configuration and settings.