.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Configuration
=============


permissions
---------------

You have the possibility to set default permissions for new categories in the same syntax like defaultm persmissions for new pages.

::
    TCEMAIN.permissions.commerce.groupid = 1
    TCEMAIN.permissions.commerce.group = 31
    TCEMAIN.permissions.commerce.everybody = 31

The config above set the groupid for new category records to 1 and the permission rights for the group and everbody to "show, editcontent, edit, new, delete". You can also use the string syntax for the permissions. If nothing was set for "TCEMAIN.permissions.commerce.", we use "TCEMAIN.permissions." as default for the rights, if something was set.
