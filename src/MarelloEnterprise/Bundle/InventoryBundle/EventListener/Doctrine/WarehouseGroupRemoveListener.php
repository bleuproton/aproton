<?php

namespace MarelloEnterprise\Bundle\InventoryBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Marello\Bundle\InventoryBundle\Entity\WarehouseGroup;
use MarelloEnterprise\Bundle\InventoryBundle\Checker\IsFixedWarehouseGroupChecker;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class WarehouseGroupRemoveListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var IsFixedWarehouseGroupChecker
     */
    protected $checker;
    
    /**
     * @param TranslatorInterface $translator
     * @param Session $session
     * @param IsFixedWarehouseGroupChecker $checker
     */
    public function __construct(
        TranslatorInterface $translator,
        Session $session,
        IsFixedWarehouseGroupChecker $checker
    ) {
        $this->translator = $translator;
        $this->session = $session;
        $this->checker = $checker;
    }
    
    /**
     * @param WarehouseGroup $warehouseGroup
     * @param LifecycleEventArgs $args
     * @throws ForbiddenException
     */
    public function preRemove(WarehouseGroup $warehouseGroup, LifecycleEventArgs $args)
    {
        if ($warehouseGroup->isSystem()) {
            $message = $this->translator->trans(
                'marelloenterprise.inventory.messages.error.warehousegroup.system_warehousegroup_deletion'
            );
        }
        if ($this->checker->check($warehouseGroup)) {
            $message = $this->translator->trans(
                'marelloenterprise.inventory.messages.error.warehousegroup.fixed_warehousegroup_deletion'
            );
        }
        if ($warehouseGroup->getWarehouseChannelGroupLink()) {
            $message = $this->translator->trans(
                'marelloenterprise.inventory.messages.error.warehousegroup.linked_warehousegroup_deletion'
            );
        }
        if (isset($message)) {
            $this->session->getFlashBag()->add('error', $message);
            throw new ForbiddenException($message);
        }
        $em = $args->getEntityManager();
        $systemGroup = $em
            ->getRepository(WarehouseGroup::class)
            ->findSystemWarehouseGroup();

        if ($systemGroup) {
            $warehouses = $warehouseGroup->getWarehouses();
            foreach ($warehouses as $warehouse) {
                $warehouse->setGroup($systemGroup);
                $em->persist($warehouse);
            }
            $em->flush();
        }
    }
}
