<?php

namespace Marello\Bundle\OroCommerceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 */
class OroCommerceSettings extends Transport
{
    const URL_FIELD = 'url';
    const CURRENCY_FIELD = 'currency';
    const KEY_FIELD = 'key';
    const USERNAME_FIELD = 'username';
    const PRODUCTUNIT_FIELD = 'productunit';
    const CUSTOMERTAXCODE_FIELD = 'customertaxcode';
    const PRICELIST_FIELD = 'pricelist';
    const PRODUCTFAMILY_FIELD = 'productfamily';
    const INVENTORYTHRESHOLD_FIELD = 'inventorythreshold';
    const LOWINVENTORYTHRESHOLD_FIELD = 'lowinventorythreshold';
    const BACKORDER_FIELD = 'backorder';
    const DELETE_REMOTE_DATA_ON_DEACTIVATION = 'deleteRemoteDataOnDeactivation';
    const DELETE_REMOTE_DATA_ON_DELETION = 'deleteRemoteDataOnDeletion';
    const DATA = 'data';

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_url", type="string", length=1024, nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_currency", type="string", length=3, nullable=false)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_key", type="string", length=1024, nullable=false)
     */
    private $key;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_username", type="string", length=1024, nullable=false)
     */
    private $userName;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_productunit", type="string", length=20, nullable=false)
     */
    private $productUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_customertaxcode", type="integer", nullable=false)
     */
    private $customerTaxCode;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_pricelist", type="integer", nullable=false)
     */
    private $priceList;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_productfamily", type="integer", nullable=false)
     */
    private $productFamily;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_inventorythreshold", type="integer", nullable=false)
     */
    private $inventoryThreshold;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_lowinvthreshold", type="integer", nullable=false)
     */
    private $lowInventoryThreshold;

    /**
     * @var string
     *
     * @ORM\Column(name="orocommerce_backorder", type="boolean", nullable=false)
     */
    private $backOrder;

    /**
     * @var bool
     *
     * @ORM\Column(name="orocommerce_deldataondeactiv", type="boolean", nullable=true)
     */
    private $deleteRemoteDataOnDeactivation;

    /**
     * @var bool
     *
     * @ORM\Column(name="orocommerce_deldataondel", type="boolean", nullable=true)
     */
    private $deleteRemoteDataOnDeletion;

    /**
     * @var array $data
     *
     * @ORM\Column(name="orocommerce_data", type="json_array", nullable=true)
     */
    protected $data;
    
    /**
     * @var ParameterBag
     */
    private $settings;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }
    
    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return $this
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param string $productUnit
     * @return $this
     */
    public function setProductUnit($productUnit)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerTaxCode()
    {
        return $this->customerTaxCode;
    }

    /**
     * @param int $customerTaxCode
     * @return $this
     */
    public function setCustomerTaxCode($customerTaxCode)
    {
        $this->customerTaxCode = $customerTaxCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param int $priceList
     * @return $this
     */
    public function setPriceList($priceList)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return int
     */
    public function getProductFamily()
    {
        return $this->productFamily;
    }

    /**
     * @param int $productFamily
     * @return $this
     */
    public function setProductFamily($productFamily)
    {
        $this->productFamily = $productFamily;

        return $this;
    }

    /**
     * @return int
     */
    public function getInventoryThreshold()
    {
        return $this->inventoryThreshold;
    }

    /**
     * @param int $inventoryThreshold
     * @return $this
     */
    public function setInventoryThreshold($inventoryThreshold)
    {
        $this->inventoryThreshold = $inventoryThreshold;

        return $this;
    }

    /**
     * @return int
     */
    public function getLowInventoryThreshold()
    {
        return $this->lowInventoryThreshold;
    }

    /**
     * @param int $lowInventoryThreshold
     * @return $this
     */
    public function setLowInventoryThreshold($lowInventoryThreshold)
    {
        $this->lowInventoryThreshold = $lowInventoryThreshold;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBackOrder()
    {
        return $this->backOrder;
    }

    /**
     * @param bool $backOrder
     * @return $this
     */
    public function setBackOrder($backOrder)
    {
        $this->backOrder = $backOrder;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleteRemoteDataOnDeactivation()
    {
        return $this->deleteRemoteDataOnDeactivation;
    }

    /**
     * @param bool $deleteRemoteDataOnDeactivation
     * @return $this
     */
    public function setDeleteRemoteDataOnDeactivation($deleteRemoteDataOnDeactivation)
    {
        $this->deleteRemoteDataOnDeactivation = $deleteRemoteDataOnDeactivation;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeleteRemoteDataOnDeletion()
    {
        return $this->deleteRemoteDataOnDeletion;
    }

    /**
     * @param boolean $deleteRemoteDataOnDeletion
     * @return OroCommerceSettings
     */
    public function setDeleteRemoteDataOnDeletion($deleteRemoteDataOnDeletion)
    {
        $this->deleteRemoteDataOnDeletion = $deleteRemoteDataOnDeletion;
        
        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    self::URL_FIELD => $this->getUrl(),
                    self::CURRENCY_FIELD => $this->getCurrency(),
                    self::KEY_FIELD => $this->getKey(),
                    self::USERNAME_FIELD => $this->getUserName(),
                    self::PRODUCTUNIT_FIELD => $this->getProductUnit(),
                    self::CUSTOMERTAXCODE_FIELD => $this->getCustomerTaxCode(),
                    self::PRICELIST_FIELD => $this->getPriceList(),
                    self::PRODUCTFAMILY_FIELD => $this->getProductFamily(),
                    self::INVENTORYTHRESHOLD_FIELD => $this->getInventoryThreshold(),
                    self::LOWINVENTORYTHRESHOLD_FIELD => $this->getLowInventoryThreshold(),
                    self::BACKORDER_FIELD => $this->isBackOrder(),
                    self::DELETE_REMOTE_DATA_ON_DEACTIVATION => $this->isDeleteRemoteDataOnDeactivation(),
                    self::DELETE_REMOTE_DATA_ON_DELETION => $this->isDeleteRemoteDataOnDeletion(),
                    self::DATA => $this->getData()
                ]
            );
        }

        return $this->settings;
    }
}
