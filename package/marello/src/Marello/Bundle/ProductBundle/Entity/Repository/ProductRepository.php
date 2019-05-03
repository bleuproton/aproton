<?php

namespace Marello\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\SalesBundle\Entity\SalesChannel;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProductRepository extends EntityRepository
{
    const PGSQL_DRIVER = 'pdo_pgsql';
    const MYSQL_DRIVER = 'pdo_mysql';

    /**
     * @var string
     */
    private $databaseDriver;
    
    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string $databaseDriver
     */
    public function setDatabaseDriver($databaseDriver)
    {
        $this->databaseDriver = $databaseDriver;
    }

    /**
     * @param SalesChannel $salesChannel
     *
     * @return Product[]
     */
    public function findByChannel(SalesChannel $salesChannel)
    {
        $qb = $this->createQueryBuilder('product');
        $qb
            ->where(
                $qb->expr()->isMemberOf(':salesChannel', 'product.channels')
            )
            ->setParameter('salesChannel', $salesChannel->getId());

        return $this->aclHelper->apply($qb->getQuery())->getResult();
    }

    /**
     * Return products for specified price list and product IDs
     *
     * @param int $salesChannel
     * @param array $productIds
     *
     * @return Product[]
     */
    public function findBySalesChannel($salesChannel, array $productIds)
    {
        if (!$productIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('product');
        $qb
            ->where(
                $qb->expr()->isMemberOf(':salesChannel', 'product.channels'),
                $qb->expr()->in('product.id', ':productIds')
            )
            ->setParameter('salesChannel', $salesChannel)
            ->setParameter('productIds', $productIds);

        return $this->aclHelper->apply($qb->getQuery())->getResult();
    }
    /**
     * @param string $sku
     *
     * @return null|Product
     */
    public function findOneBySku($sku)
    {
        $queryBuilder = $this->createQueryBuilder('product');

        $queryBuilder->andWhere('UPPER(product.sku) = :sku')
            ->setParameter('sku', strtoupper($sku));

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $pattern
     *
     * @return string[]
     */
    public function findAllSkuByPattern($pattern)
    {
        $matchedSku = [];

        $results = $this
            ->createQueryBuilder('product')
            ->select('product.sku')
            ->where('product.sku LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->getQuery()
            ->getResult();

        foreach ($results as $result) {
            $matchedSku[] = $result['sku'];
        }

        return $matchedSku;
    }

    /**
     * @param string $key
     * @return Product[]
     */
    public function findByDataKey($key)
    {
        if ($this->databaseDriver === self::PGSQL_DRIVER) {
            $formattedDataField = 'CAST(p.data as TEXT)';
        } else {
            $formattedDataField = 'p.data';
        }
        $qb = $this->createQueryBuilder('p');
        $qb
            ->where(sprintf('%s LIKE :key', $formattedDataField))
            ->setParameter('key', '%' . $key . '%');

        return $this->aclHelper->apply($qb->getQuery())->getResult();
    }
}
