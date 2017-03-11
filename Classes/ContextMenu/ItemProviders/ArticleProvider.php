<?php
namespace CommerceTeam\Commerce\ContextMenu\ItemProviders;

class ArticleProvider extends \TYPO3\CMS\Backend\ContextMenu\ItemProviders\PageProvider
{
    /**
     * @var string
     */
    protected $table = 'tx_commerce_articles';

    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'view' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.view',
            'iconIdentifier' => 'actions-document-view',
            'callbackAction' => 'viewRecord'
        ],
        'edit' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => 'editRecord'
        ],
        /* @todo fix call. disabled by now until working as expected
        'new' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.new',
            'iconIdentifier' => 'actions-document-new',
            'callbackAction' => 'newRecord'
        ],*/
        'info' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.info',
            'iconIdentifier' => 'actions-document-info',
            'callbackAction' => 'openInfoPopUp'
        ],
        /* @todo fix call. disabled by now until working as expected
        'divider1' => [
            'type' => 'divider'
        ],
        'copy' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'copy'
        ],
        'copyRelease' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.copy',
            'iconIdentifier' => 'actions-edit-copy-release',
            'callbackAction' => 'clipboardRelease'
        ],
        'cut' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.cut',
            'iconIdentifier' => 'actions-edit-cut',
            'callbackAction' => 'cut'
        ],
        'cutRelease' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.cut',
            'iconIdentifier' => 'actions-edit-cut-release',
            'callbackAction' => 'clipboardRelease'
        ],
        'pasteAfter' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.pasteafter',
            'iconIdentifier' => 'actions-document-paste-after',
            'callbackAction' => 'pasteAfter'
        ],
        'pasteInto' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.pasteinto',
            'iconIdentifier' => 'actions-document-paste-into',
            'callbackAction' => 'pasteInto'
        ],*/
        'divider2' => [
            'type' => 'divider'
        ],
        'enable' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:enable',
            'iconIdentifier' => 'actions-edit-unhide',
            'callbackAction' => 'enableRecord',
        ],
        'disable' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:disable',
            'iconIdentifier' => 'actions-edit-hide',
            'callbackAction' => 'disableRecord',
        ],
        'delete' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.delete',
            'iconIdentifier' => 'actions-edit-delete',
            'callbackAction' => 'deleteRecord',
        ],
        'history' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_misc.xlf:CM_history',
            'iconIdentifier' => 'actions-document-history-open',
            'callbackAction' => 'openHistoryPopUp',
        ],

        'exportT3d' => [
            'type' => 'divider',
        ],
        'importT3d' => [
            'type' => 'divider',
        ]
    ];

    /**
     * Checks if the provider can add items to the menu
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'tx_commerce_articles';
    }

    /**
     * Priority is set to lower then default value, in order to skip this provider if there is less generic provider available.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 100;
    }
}
