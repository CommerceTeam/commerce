<?php
namespace CommerceTeam\Commerce\Controller;

use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StatisticCompleteAggregationModuleFunctionController extends AbstractFunctionModule
{
    /**
     * @var StatisticModuleController
     */
    public $pObj;

    /**
     * @return string
     */
    public function main()
    {
        $database = $this->getDatabaseConnection();

        $result = '';
        if (GeneralUtility::_POST('fullaggregation')) {
            $endres = $database->exec_SELECTquery('MAX(crdate)', 'tx_commerce_order_articles', '1=1');
            $endtime2 = 0;
            if ($endres && ($endrow = $database->sql_fetch_row($endres))) {
                $endtime2 = $endrow[0];
            }

            $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

            $startres = $database->exec_SELECTquery(
                'MIN(crdate)',
                'tx_commerce_order_articles',
                'crdate > 0 AND deleted = 0'
            );
            if ($startres and ($startrow = $database->sql_fetch_row($startres)) and $startrow[0] != null) {
                $starttime = $startrow[0];
                $database->sql_query('truncate tx_commerce_salesfigures');
                $result .= $this->pObj->statistics->doSalesAggregation($starttime, $endtime);
            } else {
                $result .= 'no sales data available';
            }

            $endres = $database->exec_SELECTquery('MAX(crdate)', 'fe_users', '1=1');
            if ($endres and ($endrow = $database->sql_fetch_row($endres))) {
                $endtime2 = $endrow[0];
            }

            $endtime = $endtime2 > mktime(0, 0, 0) ? mktime(0, 0, 0) : strtotime('+1 hour', $endtime2);

            $startres = $database->exec_SELECTquery('MIN(crdate)', 'fe_users', 'crdate > 0 AND deleted = 0');
            if ($startres and ($startrow = $database->sql_fetch_row($startres)) and $startrow[0] != null) {
                $starttime = $startrow[0];
                $database->sql_query('truncate tx_commerce_newclients');
                $result = $this->pObj->statistics->doClientAggregation($starttime, $endtime);
            } else {
                $result .= '<br />no client data available';
            }
        } else {
            $language = $this->getLanguageService();

            $result = $language->getLL('may_take_long_periode') . '<br /><br />';
            $result .= sprintf(
                '<input type="submit" name="fullaggregation" value="%s" />',
                $language->getLL('complete_aggregation')
            );
        }

        return $result;
    }
}
