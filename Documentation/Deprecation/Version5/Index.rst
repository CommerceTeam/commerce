.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _emails:

Deprecated since 5.x
====================
FolderRepository::getFolders should not be used anymore, please use ::getFolder instead


Removed methods since 5.x
=========================

.. contents::
	:local:
	:depth: 1


.. _\CommerceTeam\Commerce\Tree\Leaf\MasterData:
.. csv-table::
	:header: Class, Hook, Replacement
	:widths: 30, 35, 35

	\CommerceTeam\Commerce\Tree\Leaf\MasterData, getRecordsByMountpoints_preLoadRecords, getRecordsByMountpointsPreLoadRecords
	, getRecordsByMountpoints_postProcessRecords, getRecordsByMountpointsPostProcessRecords


.. _\CommerceTeam\Commerce\Hook\DataMapHooks:
.. csv-table::
	:header: Class, Hook, Replacement
	:widths: 30, 35, 35

	\CommerceTeam\Commerce\Hook\DataMapHooks, moveOrders_preMoveOrder, moveOrdersPreMoveOrder
	, moveOrders_postMoveOrder, moveOrdersPostMoveOrder