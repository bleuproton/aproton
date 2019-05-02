<?php

namespace MarelloEnterprise\Bundle\ReplenishmentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ReplenishmentOrderConfigRepository extends EntityRepository
{
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
}
