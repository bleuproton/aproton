<?php

namespace Marello\Bundle\InvoiceBundle\Pdf\Request;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Marello\Bundle\InvoiceBundle\Entity\Invoice;
use Marello\Bundle\PdfBundle\Provider\Render\ConfigValuesProvider;
use Marello\Bundle\PdfBundle\Provider\RenderParametersProvider;
use Marello\Bundle\PdfBundle\Renderer\TwigRenderer;
use Marello\Bundle\PdfBundle\Request\PdfRequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class InvoicePdfRequestHandler implements PdfRequestHandlerInterface
{
    const ENTITY_ALIAS = 'invoice';

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RenderParametersProvider
     */
    private $parametersProvider;

    /**
     * @var TwigRenderer
     */
    private $renderer;

    /**
     * @param Registry $doctrine
     * @param TranslatorInterface $translator
     * @param RenderParametersProvider $parametersProvider
     * @param TwigRenderer $renderer
     */
    public function __construct(
        Registry $doctrine,
        TranslatorInterface $translator,
        RenderParametersProvider $parametersProvider,
        TwigRenderer $renderer
    ) {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->parametersProvider = $parametersProvider;
        $this->renderer = $renderer;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Request $request)
    {
        $id = $request->attributes->get('id');
        if (!$id) {
            return false;
        }
        $entity = $request->attributes->get('entity');
        if (!$entity) {
            return false;
        }
        if ($entity !== self::ENTITY_ALIAS) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request)
    {
        $entity = $this->doctrine
            ->getManagerForClass(Invoice::class)
            ->getRepository(Invoice::class)
            ->find($request->attributes->get('id'));
        if (!$entity) {
            return null;
        }
        if ($request->query->get('download')) {
            $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        } else {
            $disposition = ResponseHeaderBag::DISPOSITION_INLINE;
        }

        $filename = sprintf('%s.pdf', $this->translator->trans(
            'marello.invoice.pdf.filename.label',
            ['%entityNumber%' => $entity->getInvoiceNumber()]
        ));

        $params = $this->parametersProvider
            ->getParams($entity, [ConfigValuesProvider::SCOPE_IDENTIFIER_KEY => $entity->getSalesChannel()])
        ;
        $pdf = $this->renderer
            ->render('MarelloInvoiceBundle:Pdf:invoice.html.twig', $params)
        ;

        $response = new Response();
        $response->setContent($pdf);
        $response->headers->set('Content-Type', 'application/pdf');

        $contentDisposition = $response->headers->makeDisposition($disposition, $filename);
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }
}