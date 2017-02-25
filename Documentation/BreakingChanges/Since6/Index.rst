.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Since Version 6
===============

Removed Files
-------------

Hooks/SrfeuserregisterPi1Hook.php the commerce team does not advocates to use this extension and the hook if here by removed.


Removed functions
-----------------

FolderRepository::getFolders should not be used anymore, please use ::getFolder instead
BaseController::renderValue.NUMBERFORMAT this type of formating is possible via stdWrap.numberFormat and takes the configuration decimals, dec_point, thousands_sep so just replace NUMBERFORMAT with stdWrap.numberFormat = 1 and add stdWrap.numberFormat.deci... and so on


Removed properties
------------------

FolderRepository::initFolders $parentTitle
FolderRepository::initFolders $executeUpdateUtility


Changed methods
---------------

Ccvs::validateCreditCard now respects the parameter of the super method. To be able to check with a checksum please use Ccvs::validate instead
FolderRepository::initFolders order introduced in 5.0 is now the default and no fallback handling will be available anymore.
