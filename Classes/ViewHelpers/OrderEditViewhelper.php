<?php
namespace CommerceTeam\Commerce\ViewHelpers;

/*
 * This file is part of the TYPO3 Commerce project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use CommerceTeam\Commerce\Domain\Repository\FolderRepository;
use CommerceTeam\Commerce\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * User Class for displaying Orders.
 *
 * Class \CommerceTeam\Commerce\ViewHelpers\OrderEditViewhelper
 */
class OrderEditViewhelper
{
    /**
     * Page info.
     *
     * @var array
     */
    protected $pageinfo;

    /**
     * Return url.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * Commands.
     *
     * @var string
     */
    protected $cmd;

    /**
     * Command table.
     *
     * @var string
     */
    protected $cmd_table;

    /**
     * Module settings.
     *
     * @var array
     */
    protected $MOD_SETTINGS = [];

    /**
     * Article order_id
     * Just a hidden field.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function articleOrderId(array $parameter)
    {
        $content = htmlspecialchars($parameter['itemFormElValue']) .
            '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
            htmlspecialchars($parameter['itemFormElValue']) . '">';

        return $content;
    }

    /**
     * Article order_id
     * Just a hidden field.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function sumPriceGrossFormat(array $parameter)
    {
        $content = '<input type="text" disabled name="' . $parameter['itemFormElName'] . '" value="' .
            sprintf("%01.2f", $parameter['itemFormElValue'] / 100) . '">';

        return $content;
    }

    /**
     * Oder Articles
     * Renders the List of aricles.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function orderArticles(array $parameter)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $language = $this->getLanguageService();
        $settingsFactory = ConfigurationUtility::getInstance();

        $content = '';
        $orderArticleTable = 'tx_commerce_order_articles';
        $orderTable = 'tx_commerce_orders';

        // GET Storage PID and order_id from Data
        $orderStoragePid = $parameter['row']['pid'];
        $orderId = $parameter['row']['order_id'];

        /*
         * Select Order_articles
         */

        // @todo TS config of fields in list
        $fieldRows = ['amount', 'title', 'article_number', 'price_net', 'price_gross'];

        $titleCol = $settingsFactory->getTcaValue($orderArticleTable . '.ctrl.label');

        // Check if Orders in this folder are editable
        /**
         * Page repository.
         *
         * @var \CommerceTeam\Commerce\Domain\Repository\PageRepository $pageRepository
         */
        $pageRepository = GeneralUtility::makeInstance(\CommerceTeam\Commerce\Domain\Repository\PageRepository::class);
        $orderEditable = !empty($pageRepository->findEditFolderByUid($orderStoragePid));

        /**
         * Order article repository.
         *
         * @var \CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository $orderArticleRepository
         */
        $orderArticleRepository = GeneralUtility::makeInstance(
            \CommerceTeam\Commerce\Domain\Repository\OrderArticleRepository::class
        );
        $orderArticles = $orderArticleRepository->findByOrderIdInPage($orderId, $orderStoragePid);

        $sum = [];
        $out = '';
        if (!empty($orderArticles)) {
            /*
            * Only if we have a result
            */
            $theData[$titleCol] = '<span class="c-table">' .
                $language->sL(
                    'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:order_view.items.article_list',
                    1
                ) .
                '</span> (' . count($orderArticles) . ')';

            if ($settingsFactory->getExtConf('invoicePageID')) {
                $theData[$titleCol] .= '<a href="../index.php?id=' . $settingsFactory->getExtConf('invoicePageID') .
                    '&amp;tx_commerce_pi6[order_id]=' . $orderId . '&amp;type=' .
                    $settingsFactory->getExtConf('invoicePageType') . '" target="_blank">' .
                    $language->sL(
                        'LLL:EXT:commerce/Resources/Private/Language/locallang_be.xlf:order_view.items.print_invoice',
                        1
                    ) . ' *</a>';
            }

            $out .= '
                <tr>
                    <td class="c-headLineTable" style="width: 95%;" colspan="' . (count($fieldRows) + 1) . '">' .
                $theData[$titleCol] . '</td>
                </tr>';

            /*
             * Header colum
             */
            foreach ($fieldRows as $field) {
                $out .= '<td class="c-headLineTable"><b>' .
                    $language->sL(BackendUtility::getItemLabel($orderArticleTable, $field)) .
                    '</b></td>';
            }

            $out .= '<td class="c-headLineTable"></td></tr>';

            $cc = 0;
            $iOut = '';
            foreach ($orderArticles as $row) {
                ++$cc;
                $sum['amount'] += $row['amount'];

                if ($parameter['row']['pricefromnet'] == 1) {
                    $row['price_net'] = $row['price_net'] * $row['amount'];
                    $row['price_gross'] = $row['price_net'] * (1 + (((float) $row['tax']) / 100));
                } else {
                    $row['price_gross'] = $row['price_gross'] * $row['amount'];
                    $row['price_net'] = $row['price_gross'] / (1 + (((float) $row['tax']) / 100));
                }

                $sum['price_net_value'] += $row['price_net'] / 100;
                $sum['price_gross_value'] += $row['price_gross'] / 100;

                $row['price_net'] = sprintf("%01.2f", $row['price_net'] / 100);
                $row['price_gross'] = sprintf("%01.2f", $row['price_gross'] / 100);

                $iOut .= '<tr>';
                foreach ($fieldRows as $field) {
                    $wrap = ['', ''];
                    switch ($field) {
                        case $titleCol:
                            $iOut .= '<td>';
                            if ($orderEditable) {
                                $params = '&edit[' . $orderArticleTable . '][' . $row['uid'] . ']=edit';
                                $wrap = [
                                    '<a href="#" onclick="' .
                                    htmlspecialchars(BackendUtility::editOnClick($params)) . '">',
                                    '</a>',
                                ];
                            }
                            break;

                        case 'amount':
                            $iOut .= '<td>';
                            if ($orderEditable) {
                                $params = '&edit[' . $orderArticleTable . '][' . $row['uid'] .
                                    ']=edit&columnsOnly=amount';
                                $onclickAction = 'onclick="' .
                                    htmlspecialchars(BackendUtility::editOnClick($params)) .
                                    '"';
                                $wrap = [
                                    '<b><a href="#" ' . $onclickAction . '>' .
                                    $iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render(),
                                    '</a></b>',
                                ];
                            }
                            break;

                        case 'price_net':
                            // fall through
                        case 'price_gross':
                            $iOut .= '<td style="text-align: right">';
                            break;

                        default:
                            $iOut .= '<td>';
                    }

                    $iOut .= implode(
                        BackendUtility::getProcessedValue($orderArticleTable, $field, $row[$field], 100),
                        $wrap
                    );
                    $iOut .= '</td>';
                }

                /*
                 * Trash icon
                 */
                $iOut .= '<td></td>
					</tr>';
            }

            $out .= $iOut;
            /*
             * Cerate the summ row
             */
            $out .= '<tr>';
            $sum['price_net'] = sprintf("%01.2f", $sum['price_net_value']);
            $sum['price_gross'] = sprintf("%01.2f", $sum['price_gross_value']);

            foreach ($fieldRows as $field) {
                switch ($field) {
                    case 'price_net':
                        // fall through
                    case 'price_gross':
                        $out .= '<td class="c-headLineTable" style="text-align: right"><b>';
                        break;

                    default:
                        $out .= '<td class="c-headLineTable"><b>';
                }

                if ($sum[$field] > 0) {
                    $out .= BackendUtility::getProcessedValueExtra($orderArticleTable, $field, $sum[$field], 100);
                }

                $out .= '</b></td>';
            }

            $out .= '<td class="c-headLineTable"></td></tr>';

            /*
             * Always
             * Update sum_price_net and sum_price_gross
             * To Be shure everything is ok
             */
            $values = [
                'sum_price_gross' => $sum['price_gross_value'] * 100,
                'sum_price_net' => $sum['price_net_value'] * 100
            ];
            /**
             * Order repository.
             *
             * @var \CommerceTeam\Commerce\Domain\Repository\OrderRepository $orderRepository
             */
            $orderRepository = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Repository\OrderRepository::class
            );
            $orderRepository->updateByOrderId($orderId, $values);
        }

        $out = '
            <!--
                DB listing of elements: "' . htmlspecialchars($orderTable) . '"
            -->
            <table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">
                ' . $out . '
            </table>';
        $content .= $out;

        return $content;
    }

    /**
     * Order Status
     * Selects only the order folders from the pages List.
     *
     * @param array $data Data
     *
     * @return void
     */
    public function orderStatus(array &$data)
    {
        /*
         * Create a new data item array
         */
        $data['items'] = [];

        // Find the right pid for the Ordersfolder
        $orderPid = FolderRepository::initFolders('Orders', FolderRepository::initFolders());

        /*
         * Get the pages below $order_pid
         */

        /*
         * Check if the Current PID is below $orderPid,
         * id is below orderPid we could use the parent of
         * this record to build up the select Drop Down
         * otherwhise use the default PID
         */
        $localOrderPid = $data['row']['pid'];

        $rootline = BackendUtility::BEgetRootLine($localOrderPid);
        $rootlinePids = [];
        foreach ($rootline as $pages) {
            if (isset($pages['uid'])) {
                $rootlinePids[] = $pages['uid'];
            }
        }

        if (in_array($orderPid, $rootlinePids)) {
            /**
             * Page repository.
             *
             * @var \CommerceTeam\Commerce\Domain\Repository\PageRepository $pageRepository
             */
            $pageRepository = GeneralUtility::makeInstance(
                \CommerceTeam\Commerce\Domain\Repository\PageRepository::class
            );
            $page = $pageRepository->findByUid($localOrderPid);
            if (!empty($page)) {
                $orderPid = (int) $page['pid'];
            }
        }

        $items = \CommerceTeam\Commerce\Utility\BackendUtility::getOrderFolderSelector(
            $orderPid,
            ConfigurationUtility::getInstance()->getExtConf('OrderFolderRecursiveLevel')
        );
        $data['items'] = array_merge([['' => 0]], $items);
    }

    /**
     * Invoice Adresss
     * Renders the invoice adresss.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function invoiceAddress(array $parameter)
    {
        return $this->address($parameter, 'tt_address', $parameter['itemFormElValue']);
    }

    /**
     * Renders the crdate.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function crdate(array $parameter)
    {
        $parameter['itemFormElValue'] = date('d.m.y', $parameter['itemFormElValue']);
        $parameter['renderType'] = 'none';

        /** @var NodeFactory $nodeFactory */
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        return $nodeFactory->create($parameter)->render()['html'];
    }

    /**
     * Invoice Adresss
     * Renders the invoice adresss.
     *
     * @param array $parameter Parameter
     *
     * @return string HTML-Content
     */
    public function deliveryAddress(array $parameter)
    {
        return $this->address($parameter, 'tt_address', $parameter['itemFormElValue']);
    }

    /**
     * Address
     * Renders an address block.
     *
     * @param array $parameter Parameter
     * @param string $table Table
     * @param int $uid Record UID
     *
     * @return string HTML-Content
     */
    public function address(array $parameter, $table, $uid)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $fields = 'uid,' . ConfigurationUtility::getInstance()->getTcaValue($table . '.interface.showRecordFieldList');
        $content = '';

        /*
         * First select Data from Database
         */
        if ($data = BackendUtility::getRecord($table, $uid, $fields)) {
            $params = '&edit[' . $table . '][' . $uid . ']=edit';

            $onclickAction = 'onclick="' . htmlspecialchars(BackendUtility::editOnClick($params)) . '"';
            $iconImgTag = '<span>' .
                $iconFactory->getIconForRecord($table, $data, Icon::SIZE_SMALL)->render() .
                '</span>';
            $content .= '<span class="typo3-moduleHeader">' .
                BackendUtility::wrapClickMenuOnIcon($iconImgTag, $table, $data['uid']) .
                '<b><a href="#" ' . $onclickAction . '>' .
                htmlspecialchars(GeneralUtility::fixed_lgd_cs(
                    strip_tags(BackendUtility::getRecordTitle($table, $data)),
                    45
                )) .
                '</a></b>';

            $showRecordFieldList = GeneralUtility::trimExplode(',', ConfigurationUtility::getInstance()
                ->getTcaValue($table . '.interface.showRecordFieldList'));
            foreach ($data as $key => $value) {
                if (!in_array($key, $showRecordFieldList)) {
                    unset($data[$key]);
                } else {
                    $data[$key] = [
                        'value' => $data[$key],
                        'label' => $GLOBALS['TCA'][$table]['columns'][$key]['label'],
                    ];
                }
            }

            /** @var StandaloneView $view */
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplateRootPaths([1 => 'EXT:commerce/Resources/Private/Backend/']);
            $view->setTemplate('Address');

            $view->assign('table', $table);
            $view->assign('address', $data);

            $content .= $view->render();
        }

        $content .= '<input type="hidden" name="' . $parameter['itemFormElName'] . '" value="' .
            htmlspecialchars($parameter['itemFormElValue']) .
            '">';

        return $content;
    }

    /**
     * Frontend user orders.
     *
     * @return string
     */
    public function feUserOrders()
    {
        /**
         * Order record list.
         *
         * @var \CommerceTeam\Commerce\RecordList\OrderRecordList $dblist
         */
        $dblist = GeneralUtility::makeInstance(\CommerceTeam\Commerce\RecordList\OrderRecordList::class);
        $dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
        $dblist->thumbs = $this->getBackendUser()->uc['thumbnailsByDefault'];
        $dblist->returnUrl = $this->returnUrl;
        $dblist->allFields = 1;
        $dblist->localizationView = $this->MOD_SETTINGS['localization'];
        $dblist->showClipboard = 0;

        // CB is the clipboard command array
        $clipBoardCommands = GeneralUtility::_GET('CB');
        if ($this->cmd == 'setCB') {
            // CBH is all the fields selected for the clipboard, CBC is the checkbox fields
            // which were checked. By merging we get a full array of checked/unchecked
            // elements
            // This is set to the 'el' array of the CB after being parsed so only the table
            // in question is registered.
            $clipBoardCommands['el'] = $dblist->clipObj->cleanUpCBC(
                array_merge(GeneralUtility::_POST('CBH'), GeneralUtility::_POST('CBC')),
                $this->cmd_table
            );
        }
        $dblist->start(null, 'tx_commerce_orders', 0);

        $dblist->generateList();

        return $dblist->HTMLcode;
    }


    /**
     * Get backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get controller document template.
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getControllerDocumentTemplate()
    {
        // $GLOBALS['SOBE'] might be any kind of PHP class (controller most
        // of the times) These class do not inherit from any common class,
        // but they all seem to have a "doc" member
        return $GLOBALS['SOBE']->doc;
    }
}
