<?php

namespace Marello\Bundle\CustomerBundle\Controller;

use Marello\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomerController extends AbstractController
{
    /**
     * @Route(path="/", name="marello_customer_index")
     * @Template
     * @AclAncestor("marello_customer_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return ['entity_class' => Customer::class];
    }

    /**
     * @Route(path="/view/{id}", requirements={"id"="\d+"}, name="marello_customer_view")
     * @Template
     * @AclAncestor("marello_customer_view")
     *
     * @param Customer $customer
     *
     * @return array
     */
    public function viewAction(Customer $customer)
    {
        $entityClass = $this->get('oro_entity.routing_helper')->resolveEntityClass('marellocustomers');
        $manager = $this->get('oro_activity_list.manager');
        $results = $manager->getListData(
            $entityClass,
            1000,
            [],
            []
        );
        
        return ['entity' => $customer];
    }

    /**
     * @Route(
     *     path="/create",
     *     methods={"GET", "POST"},
     *     name="marello_customer_create"
     * )
     * @Template("@MarelloCustomer/Customer/update.html.twig")
     * @AclAncestor("marello_customer_create")
     *
     * @param Request $request
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        return $this->update($request);
    }

    /**
     * @Route(
     *     path="/update/{id}",
     *     methods={"GET", "POST"},
     *     requirements={"id"="\d+"},
     *     name="marello_customer_update"
     * )
     * @Template
     * @AclAncestor("marello_customer_update")
     *
     * @param Request  $request
     * @param Customer $customer
     *
     * @return array
     */
    public function updateAction(Request $request, Customer $customer)
    {
        return $this->update($request, $customer);
    }

    /**
     * @param Request $request
     * @param Customer|null $customer
     *
     * @return mixed
     */
    private function update(Request $request, Customer $customer = null)
    {
        if (!$customer) {
            $customer = new Customer();
        }

        return $this->get('oro_form.model.update_handler')
        ->handleUpdate(
            $customer,
            $this->get('marello_customer.form'),
            function (Customer $entity) {
                return [
                    'route' => 'marello_customer_update',
                    'parameters' => ['id' => $entity->getId()]
                ];
            },
            function (Customer $entity) {
                return [
                    'route' => 'marello_customer_view',
                    'parameters' => ['id' => $entity->getId()]
                ];
            },
            $this->get('translator')->trans('marello.order.messages.success.customer.saved'),
            $this->get('marello_customer.form.handler.customer')
        );
    }
}
