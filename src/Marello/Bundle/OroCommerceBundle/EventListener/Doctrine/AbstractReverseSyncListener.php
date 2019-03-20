<?php

namespace Marello\Bundle\OroCommerceBundle\EventListener\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractReverseSyncListener
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ServiceLink
     */
    protected $syncScheduler;

    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var UnitOfWork
     *
     */
    protected $unitOfWork;

    /**
     * @var array
     */
    protected $processedEntities = [];

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param ServiceLink $schedulerServiceLink
     * @param SymmetricCrypterInterface $crypter
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ServiceLink $schedulerServiceLink,
        SymmetricCrypterInterface $crypter
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->syncScheduler = $schedulerServiceLink;
        $this->crypter = $crypter;
    }

    /**
     * @param EntityManager $entityManager
     */
    protected function init(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->unitOfWork = $entityManager->getUnitOfWork();

        // check for logged user is for confidence that data changes mes from UI, not from sync process.
        if (!$this->tokenStorage->getToken() || !$this->tokenStorage->getToken()->getUser()) {
            return;
        }
    }

    /**
     * @param array $connection_parameters
     * @param array $excludedKeys
     * @return string
     */
    protected function generateConnectionParametersKey(array $connection_parameters, array $excludedKeys = [])
    {
        ksort($connection_parameters);
        $key = '';
        foreach ($connection_parameters as $k => $v) {
            if (!in_array($k, $excludedKeys)) {
                $key = sprintf('%s/%s:%s', $key, $k, $v);
            }
        }

        return hash('crc32', $key);
    }
}
