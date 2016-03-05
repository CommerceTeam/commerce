<?php
namespace CommerceTeam\Commerce\Domain\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Libary for Frontend-Rendering of attribute values. This class
 * should be used for all Fronten-Rendering, no Database calls
 * to the commerce tables should be made directly
 * This Class is inhertited from
 * \CommerceTeam\Commerce\Domain\Model\AbstractEntity, all
 * basic Database calls are made from a separate Database Class
 * Main script class for the handling of attribute Values. An attribute_value
 * desribes the technical data of an article
 * Do not acces class variables directly, allways use the get and set methods,
 * variables will be changed in php5 to private.
 *
 * Class \CommerceTeam\Commerce\Domain\Model\AttributeValue
 *
 * @author 2005-2011 Ingo Schmitt <is@marketing-factory.de>
 */
class AttributeValue extends AbstractEntity
{
    /**
     * Database class name.
     *
     * @var string
     */
    protected $repositoryClass = \CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository::class;

    /**
     * Database connection.
     *
     * @var \CommerceTeam\Commerce\Domain\Repository\AttributeValueRepository
     */
    public $databaseConnection;

    /**
     * Field list.
     *
     * @var array
     */
    protected $fieldlist = array(
        'title',
        'value',
        'showvalue',
        'icon',
        'l18n_parent',
    );

    /**
     * Title of Attribute (private).
     *
     * @var string
     */
    protected $title = '';

    /**
     * The Value for.
     *
     * @var string
     */
    protected $value = '';

    /**
     * If this value should be shown in Fe output.
     *
     * @var bool show value
     */
    protected $showvalue = 1;

    /**
     * Icon for this Value.
     *
     * @var string icon
     */
    protected $icon = '';

    /**
     * Show icon.
     *
     * @var string
     */
    protected $showicon;

    /**
     * Constructor, basically calls init.
     *
     * @param int $uid Attribute value uid
     * @param int $languageUid Language uid
     */
    public function __construct($uid, $languageUid = 0)
    {
        if ((int) $uid) {
            $this->init($uid, $languageUid);
        }
    }

    /**
     * Init Class.
     *
     * @param int $uid Attribute
     * @param int $languageUid Language uid, default 0
     *
     * @return void
     */
    public function init($uid, $languageUid = 0)
    {
        $this->uid = (int) $uid;
        $this->lang_uid = (int) $languageUid;

        $hooks = \CommerceTeam\Commerce\Factory\HookFactory::getHooks('Domain/Model/AttributeValue', 'init');
        foreach ($hooks as $hook) {
            if (method_exists($hook, 'postinit')) {
                $hook->postinit($this);
            }
        }
    }

    /**
     * Overwrite get_attributes as attribute_values can't have attributes.
     *
     * @return bool FALSE
     */
    public function getAttributes()
    {
        return false;
    }

    /**
     * Gets the icon for this value.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Get show value.
     *
     * @return bool
     */
    public function getShowvalue()
    {
        return $this->showvalue;
    }

    /**
     * Gets the attribute value.
     *
     * @param bool $checkvalue Check if value should be show in FE
     *
     * @return string title
     */
    public function getValue($checkvalue = false)
    {
        if ($checkvalue && $this->showvalue) {
            return $this->value;
        } elseif ($checkvalue == false) {
            return $this->value;
        }

        return false;
    }
}
