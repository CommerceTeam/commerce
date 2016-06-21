mod.commerce_category.tx_commerce_categories.previewPageID =
mod.commerce_category.tx_commerce_products.previewPageID =
mod.commerce_category.tx_commerce_articles.previewPageID =

options.contextMenu.categoryTree.disableItems =

options.contextMenu.table.tx_commerce_categories {
    disableItems =
    items {
        100 = ITEM
        100 {
            name = view
            label = LLL:EXT:lang/locallang_core.xlf:cm.view
            iconName = actions-document-view
            displayCondition = canBeViewed != 0
            callbackAction = viewPage
        }

        200 = DIVIDER

        300 = ITEM
        300 {
            name = disable
            label = LLL:EXT:lang/locallang_common.xlf:disable
            iconName = actions-edit-hide
            displayCondition = getRecord|hidden = 0 && canBeDisabledAndEnabled != 0
            callbackAction = disableRecord
        }

        400 = ITEM
        400 {
            name = enable
            label = LLL:EXT:lang/locallang_common.xlf:enable
            iconName = actions-edit-unhide
            displayCondition = getRecord|hidden = 1 && canBeDisabledAndEnabled != 0
            callbackAction = enableRecord
        }

        500 = ITEM
        500 {
            name = edit
            label = LLL:EXT:lang/locallang_core.xlf:cm.edit
            iconName = actions-page-open
            displayCondition = canBeEdited != 0
            callbackAction = editPageProperties
        }

        600 = ITEM
        600 {
            name = info
            label = LLL:EXT:lang/locallang_core.xlf:cm.info
            iconName = actions-document-info
            displayCondition = canShowInfo != 0
            callbackAction = openInfoPopUp
        }

        700 = ITEM
        700 {
            name = history
            label = LLL:EXT:lang/locallang_misc.xlf:CM_history
            iconName = actions-document-history-open
            displayCondition = canShowHistory != 0
            callbackAction = openHistoryPopUp
        }

        800 = DIVIDER

        // disabled by now until working as expected
        900 = SUBMENU
        900 {
            label = LLL:EXT:lang/locallang_core.xlf:cm.copyPasteActions

            100 = ITEM
            100 {
                name = new
                label = LLL:EXT:lang/locallang_core.xlf:cm.new
                iconName = actions-page-new
                displayCondition = canCreateNewPages != 0
                callbackAction = newPageWizard
            }

            200 = DIVIDER

            300 = ITEM
            300 {
                name = cut
                label = LLL:EXT:lang/locallang_core.xlf:cm.cut
                iconName = actions-edit-cut
                displayCondition = isInCutMode = 0 && canBeCut != 0 && isMountPoint != 1
                callbackAction = enableCutMode
            }

            400 = ITEM
            400 {
                name = cut
                label = LLL:EXT:lang/locallang_core.xlf:cm.cut
                iconName = actions-edit-cut-release
                displayCondition = isInCutMode = 1 && canBeCut != 0
                callbackAction = disableCutMode
            }

            500 = ITEM
            500 {
                name = copy
                label = LLL:EXT:lang/locallang_core.xlf:cm.copy
                iconName = actions-edit-copy
                displayCondition = isInCopyMode = 0
                callbackAction = enableCopyMode
            }

            600 = ITEM
            600 {
                name = copy
                label = LLL:EXT:lang/locallang_core.xlf:cm.copy
                iconName = actions-edit-copy-release
                displayCondition = isInCopyMode = 1
                callbackAction = disableCopyMode
            }

            700 = ITEM
            700 {
                name = pasteInto
                label = LLL:EXT:lang/locallang_core.xlf:cm.pasteinto
                iconName = actions-document-paste-into
                displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedInto != 0
                callbackAction = pasteIntoNode
            }

            800 = ITEM
            800 {
                name = pasteAfter
                label = LLL:EXT:lang/locallang_core.xlf:cm.pasteafter
                iconName = actions-document-paste-after
                displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedAfter != 0
                callbackAction = pasteAfterNode
            }
        }
        900 >

        1000 = ITEM
        1000 {
            name = delete
            label = LLL:EXT:lang/locallang_core.xlf:cm.delete
            iconName = actions-edit-delete
            displayCondition = canBeRemoved != 0 && isMountPoint != 1
            callbackAction = removeNode
        }
    }
}

options.contextMenu.table.tx_commerce_products {
    disableItems =
    items {
        100 = ITEM
        100 {
            name = view
            label = LLL:EXT:lang/locallang_core.xlf:cm.view
            iconName = actions-document-view
            displayCondition = canBeViewed != 0
            callbackAction = viewPage
        }

        200 = DIVIDER

        300 = ITEM
        300 {
            name = disable
            label = LLL:EXT:lang/locallang_common.xlf:disable
            iconName = actions-edit-hide
            displayCondition = getRecord|hidden = 0 && canBeDisabledAndEnabled != 0
            callbackAction = disableRecord
        }

        400 = ITEM
        400 {
            name = enable
            label = LLL:EXT:lang/locallang_common.xlf:enable
            iconName = actions-edit-unhide
            displayCondition = getRecord|hidden = 1 && canBeDisabledAndEnabled != 0
            callbackAction = enableRecord
        }

        500 = ITEM
        500 {
            name = edit
            label = LLL:EXT:lang/locallang_core.xlf:cm.edit
            iconName = actions-page-open
            displayCondition = canBeEdited != 0
            callbackAction = editPageProperties
        }

        600 = ITEM
        600 {
            name = info
            label = LLL:EXT:lang/locallang_core.xlf:cm.info
            iconName = actions-document-info
            displayCondition = canShowInfo != 0
            callbackAction = openInfoPopUp
        }

        700 = ITEM
        700 {
            name = history
            label = LLL:EXT:lang/locallang_misc.xlf:CM_history
            iconName = actions-document-history-open
            displayCondition = canShowHistory != 0
            callbackAction = openHistoryPopUp
        }

        800 = DIVIDER

        // disabled by now until working as expected
        900 = SUBMENU
        900 {
            label = LLL:EXT:lang/locallang_core.xlf:cm.copyPasteActions

            100 = ITEM
            100 {
                name = new
                label = LLL:EXT:lang/locallang_core.xlf:cm.new
                iconName = actions-page-new
                displayCondition = canCreateNewPages != 0
                callbackAction = newPageWizard
            }

            200 = DIVIDER

            300 = ITEM
            300 {
                name = cut
                label = LLL:EXT:lang/locallang_core.xlf:cm.cut
                iconName = actions-edit-cut
                displayCondition = isInCutMode = 0 && canBeCut != 0 && isMountPoint != 1
                callbackAction = enableCutMode
            }

            400 = ITEM
            400 {
                name = cut
                label = LLL:EXT:lang/locallang_core.xlf:cm.cut
                iconName = actions-edit-cut-release
                displayCondition = isInCutMode = 1 && canBeCut != 0
                callbackAction = disableCutMode
            }

            500 = ITEM
            500 {
                name = copy
                label = LLL:EXT:lang/locallang_core.xlf:cm.copy
                iconName = actions-edit-copy
                displayCondition = isInCopyMode = 0
                callbackAction = enableCopyMode
            }

            600 = ITEM
            600 {
                name = copy
                label = LLL:EXT:lang/locallang_core.xlf:cm.copy
                iconName = actions-edit-copy-release
                displayCondition = isInCopyMode = 1
                callbackAction = disableCopyMode
            }

            700 = ITEM
            700 {
                name = pasteInto
                label = LLL:EXT:lang/locallang_core.xlf:cm.pasteinto
                iconName = actions-document-paste-into
                displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedInto != 0
                callbackAction = pasteIntoNode
            }

            800 = ITEM
            800 {
                name = pasteAfter
                label = LLL:EXT:lang/locallang_core.xlf:cm.pasteafter
                iconName = actions-document-paste-after
                displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedAfter != 0
                callbackAction = pasteAfterNode
            }
        }
        900 >


        1000 = ITEM
        1000 {
            name = delete
            label = LLL:EXT:lang/locallang_core.xlf:cm.delete
            iconName = actions-edit-delete
            displayCondition = canBeRemoved != 0 && isMountPoint != 1
            callbackAction = removeNode
        }
    }
}

options.contextMenu.table.tx_commerce_articles {
    disableItems =
    items {
        100 = ITEM
        100 {
            name = view
            label = LLL:EXT:lang/locallang_core.xlf:cm.view
            iconName = actions-document-view
            displayCondition = canBeViewed != 0
            callbackAction = viewPage
        }

        200 = DIVIDER

        300 = ITEM
        300 {
            name = disable
            label = LLL:EXT:lang/locallang_common.xlf:disable
            iconName = actions-edit-hide
            displayCondition = getRecord|hidden = 0 && canBeDisabledAndEnabled != 0
            callbackAction = disableRecord
        }

        400 = ITEM
        400 {
            name = enable
            label = LLL:EXT:lang/locallang_common.xlf:enable
            iconName = actions-edit-unhide
            displayCondition = getRecord|hidden = 1 && canBeDisabledAndEnabled != 0
            callbackAction = enableRecord
        }

        500 = ITEM
        500 {
            name = edit
            label = LLL:EXT:lang/locallang_core.xlf:cm.edit
            iconName = actions-page-open
            displayCondition = canBeEdited != 0
            callbackAction = editPageProperties
        }

        600 = ITEM
        600 {
            name = info
            label = LLL:EXT:lang/locallang_core.xlf:cm.info
            iconName = actions-document-info
            displayCondition = canShowInfo != 0
            callbackAction = openInfoPopUp
        }

        700 = ITEM
        700 {
            name = history
            label = LLL:EXT:lang/locallang_misc.xlf:CM_history
            iconName = actions-document-history-open
            displayCondition = canShowHistory != 0
            callbackAction = openHistoryPopUp
        }

        800 = DIVIDER

        // cut and copy/paste are currently disabled because an
        // article belongs to a products and only to that one
        900 = SUBMENU
        900 {
            label = LLL:EXT:lang/locallang_core.xlf:cm.copyPasteActions

            300 = ITEM
            300 {
                name = cut
                label = LLL:EXT:lang/locallang_core.xlf:cm.cut
                iconName = actions-edit-cut
                displayCondition = isInCutMode = 0 && canBeCut != 0 && isMountPoint != 1
                callbackAction = enableCutMode
            }

            400 = ITEM
            400 {
                name = cut
                label = LLL:EXT:lang/locallang_core.xlf:cm.cut
                iconName = actions-edit-cut-release
                displayCondition = isInCutMode = 1 && canBeCut != 0
                callbackAction = disableCutMode
            }

            500 = ITEM
            500 {
                name = copy
                label = LLL:EXT:lang/locallang_core.xlf:cm.copy
                iconName = actions-edit-copy
                displayCondition = isInCopyMode = 0
                callbackAction = enableCopyMode
            }

            600 = ITEM
            600 {
                name = copy
                label = LLL:EXT:lang/locallang_core.xlf:cm.copy
                iconName = actions-edit-copy-release
                displayCondition = isInCopyMode = 1
                callbackAction = disableCopyMode
            }

            700 = ITEM
            700 {
                name = pasteInto
                label = LLL:EXT:lang/locallang_core.xlf:cm.pasteinto
                iconName = actions-document-paste-into
                displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedInto != 0
                callbackAction = pasteIntoNode
            }

            800 = ITEM
            800 {
                name = pasteAfter
                label = LLL:EXT:lang/locallang_core.xlf:cm.pasteafter
                iconName = actions-document-paste-after
                displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedAfter != 0
                callbackAction = pasteAfterNode
            }
        }
        900 >

        1000 = ITEM
        1000 {
            name = delete
            label = LLL:EXT:lang/locallang_core.xlf:cm.delete
            iconName = actions-edit-delete
            displayCondition = canBeRemoved != 0 && isMountPoint != 1
            callbackAction = removeNode
        }
    }
}