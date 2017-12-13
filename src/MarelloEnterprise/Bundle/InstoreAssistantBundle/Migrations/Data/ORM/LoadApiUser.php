<?php
namespace MarelloEnterprise\Bundle\InstoreAssistantBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

class LoadApiUser extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const INSTORE_API_USERNAME = 'instore-assistant';

    /** @var ContainerInterface $container */
    protected $container;

    /** @var UserManager $userManager */
    protected $userManager;

    /** @var Role $role */
    protected $role;

    /** @var EntityManager $em */
    protected $em;

    /** @var Organization $organization */
    protected $organization;

    /** @var BusinessUnit $businessUnit */
    protected $businessUnit;

    /** @var array $data */
    protected $data = [
        'username' => self::INSTORE_API_USERNAME,
        'firstname' => 'Instore',
        'lastname' => 'Assistant'
    ];

    /**
     * {@inheritdoc}
     * @return array
     */
    public function getDependencies()
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
            LoadApiUserRole::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    protected function initSupportingEntities(ObjectManager $manager = null)
    {
        if ($manager) {
            $this->em = $manager;
        }
        $this->userManager  = $this->container->get('oro_user.manager');
        $this->organization = $this->getReference('default_organization');
        $this->role = $this->em->getRepository('OroUserBundle:Role')->findOneBy(
            array('role' => LoadApiUserRole::ROLE_INSTORE_ASSISTANT_API_USER)
        );
        $this->businessUnit = $this->em->getRepository('OroOrganizationBundle:BusinessUnit')->findOneBy(['name' => 'Main']);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function load(ObjectManager $manager)
    {
        $this->initSupportingEntities($manager);
        $this->loadApiUser();
    }

    /**
     * Load users
     *
     * @return void
     */
    public function loadApiUser()
    {
        $firstName = $this->data['firstname'];
        $lastName = $this->data['lastname'];
        $username = $this->data['username'];
        $email = sprintf('%s@%s',$this->data['username'], gethostname());

        $user = $this->createUser(
            $username,
            $email,
            $firstName,
            $lastName,
            $this->role
        );

        $user->setPlainPassword($username);
        $this->userManager->updatePassword($user);
        $this->userManager->updateUser($user);
        $this->em->flush();
    }

    /**
     * Creates a user
     *
     * @param  string    $username
     * @param  string    $email
     * @param  string    $firstName
     * @param  string    $lastName
     * @param  mixed     $role
     * @return User
     */
    private function createUser($username, $email, $firstName, $lastName, $role)
    {
        /** @var $user User */
        $user = $this->userManager->createUser();

        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setOwner($this->businessUnit);
        $user->addBusinessUnit($this->businessUnit);
        $user->addRole($role);
        $user->setOrganization($this->organization);
        $user->addOrganization($this->organization);

        return $user;
    }

}
