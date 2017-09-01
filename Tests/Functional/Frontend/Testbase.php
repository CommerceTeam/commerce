<?php
namespace CommerceTeam\Commerce\Tests\Functional\Frontend;

class Testbase extends \TYPO3\TestingFramework\Core\Testbase
{
    /**
     * Define TYPO3_MODE to FE
     */
    public function defineTypo3ModeFe()
    {
        define('TYPO3_MODE', 'FE');
    }
}
