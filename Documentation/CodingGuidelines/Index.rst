.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


Commerce Coding Guidelines
==========================


CGL
---
The TYPO3 Commerce defines the latest Version of the TYPO3 Core CGL to be used for TYPO3 Commerce.
All new code must be created by the CGL, patches should also be fix CGL issues.


Deprecated log
--------------
TYPO3 Commerce will use the TYPO3 deprecated log for old methods inside commerce.
Deprecated methods will be deleted from the code not earlier than one year after adding to the log.


Versions
--------
TYPO3 Commerce will use the "strict version" scheme. Bugfixes and security issues will be added at
least to the current TRUNK version and the latest old stable version. Features will only be added to
the TRUNK version. All versions will have a tag inside the SVN.


Maintainance of the code
------------------------

Write Access
____________
All members of the TYPO3 Commerce project have write access to the SVN


TYPO3 Core Mailinglist
______________________
A project has been set up at forge https://forge.typo3.org/projects/extension-commerce/. All patches
should be posted in addition with RFC or RFI to the slack channel https://typo3.slack.com/messages/commerce/,
everyone is allowed to post to this list!


Approval
________
All patches must have at least a "*+1 on testing*" for approval to commerce. Additional
"*+1 on reading*" are welcome! A commit to the SVN will be made by a commerce team member.


FYI96
_____
Small bugfixes, additional hooks could be announced by a FYI96. If no protest on the channel will
be posted this issue can be commited to the code.

Nobrainer
_________
Nobrainer, comments in the code, CGL changes can be made as direct commit to the code without any posting


Twitter
_______
The official hash-tag for twitter-posts ist #t3commerce
