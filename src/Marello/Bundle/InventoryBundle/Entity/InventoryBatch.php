<?php

namespace Marello\Bundle\InventoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Marello\Bundle\CoreBundle\DerivedProperty\DerivedPropertyAwareInterface;
use Marello\Bundle\CoreBundle\Model\EntityCreatedUpdatedAtTrait;
use Marello\Bundle\InventoryBundle\Model\ExtendInventoryBatch;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation as Oro;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\AuditableOrganizationAwareTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @Oro\Config(
 *      routeView="marello_inventory_batch_view",
 *      routeName="marello_inventory_batch_index",
 *      routeCreate="marello_inventory_batch_create",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-cubes"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @ORM\Table(
 *      name="marello_inventory_batch"
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class InventoryBatch extends ExtendInventoryBatch implements
    DerivedPropertyAwareInterface,
    OrganizationAwareInterface
{
    use EntityCreatedUpdatedAtTrait;
    use AuditableOrganizationAwareTrait;
    
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="batch_number", type="string", unique=true, nullable=true)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $batchNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="batch_reference", type="string", nullable=true)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $batchReference;


    /**
     * @var string
     *
     * @ORM\Column(name="purchase_reference", type="string", nullable=true)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $purchaseReference;

    /**
     * @var int
     *
     * @ORM\Column(name="quantity", type="integer")
     * @Oro\ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $quantity = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $expirationDate;

    /**
     * @var int
     *
     * @ORM\Column(name="purchase_price", type="money")
     * @Oro\ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $purchasePrice;

    /**
     * @var int
     *
     * @ORM\Column(name="total_price", type="money")
     * @Oro\ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $totalPrice;
    
    /**
     * @ORM\ManyToOne(targetEntity="Marello\Bundle\InventoryBundle\Entity\InventoryLevel", inversedBy="inventoryBatches")
     * @ORM\JoinColumn(name="inventory_level_id", referencedColumnName="id")
     * @Oro\ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="marello.inventory.inventorylevel.entity_label"
     *          },
     *          "importexport"={
     *              "full"=true
     *          },
     *          "dataaudit"={
     *              "auditable"=false
     *          }
     *      }
     * )
     *
     * @var InventoryLevel
     */
    protected $inventoryLevel;

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
    
    /**
     * @param int $id
     */
    public function setDerivedProperty($id)
    {
        if (!$this->batchNumber) {
            $this->setBatchNumber(sprintf('%09d', $id));
        }
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('#%s', $this->batchNumber);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBatchNumber()
    {
        return $this->batchNumber;
    }

    /**
     * @param string $batchNumber
     * @return InventoryBatch
     */
    public function setBatchNumber($batchNumber)
    {
        $this->batchNumber = $batchNumber;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getBatchReference()
    {
        return $this->batchReference;
    }

    /**
     * @param string $batchReference
     * @return InventoryBatch
     */
    public function setBatchReference($batchReference)
    {
        $this->batchReference = $batchReference;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getPurchaseReference()
    {
        return $this->purchaseReference;
    }

    /**
     * @param string $purchaseReference
     * @return InventoryBatch
     */
    public function setPurchaseReference($purchaseReference)
    {
        $this->purchaseReference = $purchaseReference;
        
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return InventoryBatch
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param \DateTime $expirationDate
     * @return InventoryBatch
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;
        
        return $this;
    }

    /**
     * @return int
     */
    public function getPurchasePrice()
    {
        return $this->purchasePrice;
    }

    /**
     * @param int $purchasePrice
     * @return InventoryBatch
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
        
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * @param int $totalPrice
     * @return InventoryBatch
     */
    public function setTotalPrice($totalPrice)
    {
        $this->totalPrice = $totalPrice;
        
        return $this;
    }

    /**
     * @return InventoryLevel
     */
    public function getInventoryLevel()
    {
        return $this->inventoryLevel;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @return InventoryBatch
     */
    public function setInventoryLevel(InventoryLevel $inventoryLevel)
    {
        $this->inventoryLevel = $inventoryLevel;
        
        return $this;
    }
}
