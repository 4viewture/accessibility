<?php

declare(strict_types=1);

namespace FourViewture\Accessibility\Backend\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StandardController
{
    /** @var ResponseFactoryInterface */
    protected $responseFactory;

    /** @var StreamFactoryInterface */
    protected $streamFactory;

    /** @var ModuleTemplateFactory */
    protected $moduleTemplateFactory;

    /** @var IconFactory */
    protected $iconFactory;

    /** @var UriBuilder */
    protected $uriBuilder;

    protected $menuEntries = [
        ImageAlternativeTextController::class => [
            'route' => 'tx_accessibility_image_alternative_text',
            'image' => 'EXT:accessibility/Resources/Public/Module/Barrierefreiheit.png',
            'badge' => 'kostenfrei',
            'visualBadge' => 'status-status-checked',
            'label' => 'Fehlende Alternativtexte finden und erstellen',
            'description' => 'Finden Sie Bilder ohne alternativen Text. Passen Sie Bilder schnell und übersichtlich an.',
            'actions' => [
                [
                    'route' => 'tx_accessibility_image_alternative_text',
                    'label' => 'Bearbeiten',
                    'icon' => 'actions-open',
                ]
            ]
        ],
        'externalServicesBarrierFree' => [
            'uri' => 'https://4viewture.de/produkte/drk-hosting-pilot/begriffe/barrierefreiheitserklaerung/',
            'target' => '_blank',
            'image' => 'EXT:accessibility/Resources/Public/Module/BarrierefreiheitErklaerung.png',
            'badge' => 'kostenfrei',
            'badgeClass' => 'badge-warning',
            'visualBadge' => 'actions-info-circle',
            'label' => 'Informationen zur Barrierefreiheitserklärung',
            'description' => 'Nutzen Sie das Plugin zum Erstellen Ihrer Barrierefreiheitserklaerung',
            'actions' => [
                [
                    'uri' => 'https://4viewture.de/produkte/drk-hosting-pilot/begriffe/barrierefreiheitserklaerung/',
                    'label' => 'Mehr Informationen',
                    'icon' => 'actions-window-open',
                    'target' => '_blank'
                ]
            ]
        ],
        'alternativeTextInfo' => [
            'uri' => 'https://4viewture.de/produkte/drk-hosting-pilot/begriffe/alternativtext/',
            'target' => '_blank',
            'image' => 'EXT:accessibility/Resources/Public/Module/BarrierefreiheitAlt.png',
            'badge' => 'kostenfrei',
            'visualBadge' => 'actions-info-circle',
            'label' => 'Erfahren Sie mehr zum Thema Alternativtexte',
            'description' => 'Erfahren Sie, wie man schnell gute Alternativtexte erstellt.',
            'actions' => [
                [
                    'uri' => 'https://4viewture.de/produkte/drk-hosting-pilot/begriffe/alternativtext/',
                    'label' => 'Mehr Informationen',
                    'icon' => 'actions-window-open',
                    'target' => '_blank'
                ]
            ]
        ],
        'aiServices' => [
            'uri' => 'https://4viewture.de/wcag-ai',
            'target' => '_blank',
            'image' => 'EXT:accessibility/Resources/Public/Module/BarrierefreiheitAI.png',
            'badge' => 'coming soon',
            'badgeClass' => 'badge-warning',
            'visualBadge' => 'actions-info-circle',
            'label' => 'Lassen Sie die Alternativtexte von unserer KI erzeugen',
            'description' => 'Unsere KI nutzt die Inhalte Ihrer Webseiten um die Bilder entsprechend Ihrem Einsatz zu beschreiben',
            'actions' => [
                [
                    'uri' => 'https://4viewture.de/wcag-ai',
                    'label' => 'Mehr Informationen',
                    'icon' => 'actions-window-open',
                    'target' => '_blank'
                ]
            ]
        ],
        'externalServices' => [
            'uri' => 'https://4viewture.de/wcag',
            'target' => '_blank',
            'image' => 'EXT:accessibility/Resources/Public/Module/BarrierefreiheitWCAG.png',
            'badge' => 'coming soon',
            'badgeClass' => 'badge-warning',
            'visualBadge' => 'actions-info-circle',
            'label' => 'Externe Prüfung ihrer Internetseite',
            'description' => 'Wir prüfen Ihre Internetseite und geben Ihnen Feedback zum Stand der Barrierefreiheit Ihrer Internetseite',
            'actions' => [
                [
                    'uri' => 'https://4viewture.de/wcag',
                    'label' => 'Mehr Informationen',
                    'icon' => 'actions-window-open',
                    'target' => '_blank'
                ]
            ]
        ],

    ];

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
    )

    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $template = $this->moduleTemplateFactory->create($request);
        $template->assign('menuEntries', $this->menuEntries);
        return $template->renderResponse('Backend/Standard');
    }
}
