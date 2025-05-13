<?php

declare(strict_types=1);

namespace FourViewture\Accessibility\Backend\Controller;

use Doctrine\DBAL\Result;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

class ImageAlternativeTextController
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
        $template->setTitle('Bilder ohne Alternativtext');

        $button = $template->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
        $button->setTitle('Schließen')
            ->setHref($this->uriBuilder->buildUriFromRoute('tx_accessibility_standard'))
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL))
            ->setShowLabelText(1);
        $template->getDocHeaderComponent()->getButtonBar()->addButton($button);

        $records = $this->getRecords();

        $queryParams = $request->getQueryParams();
        $currentPage = (int)($queryParams['page'] ?? 1);

        $paginator = GeneralUtility::makeInstance(
            ArrayPaginator::class,
            $records->fetchAllAssociative(),
            $currentPage,
            100
        );

        $pagination = GeneralUtility::makeInstance(SimplePagination::class, $paginator);

        // Template-Daten zur Ausgabe
        $template->assignMultiple([
            'records' => $paginator->getPaginatedItems(),
            'pagination' => $pagination,
            'currentPage' => $currentPage,
            // 'totalPages' => $paginator->getTotalPages(),
            #'baseUri' => (GeneralUtility::makeInstance(UriBuilder::class))->buildUriFromRoute(
            #    'my_custom_route', ['page' => 1] // Beispiel-Routing
            #),
        ]);

        return $template->renderResponse('Backend/ImageAlternativeTextController');
    }

    protected function getRecords(): Result
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_metadata');
        $qb = $connection->createQueryBuilder();

        $records = $connection->prepare(
            '
                SELECT
                sys_file . uid as file_uid,
                sys_file . identifier,
                sys_file_metadata . uid as metadata_uid,
                sys_file_metadata . alternative,
                sys_file_metadata . title,
                sys_file . mime_type,
                (
                SELECT COUNT(ref_uid)
                    FROM sys_refindex
                    WHERE ref_uid = sys_file . uid
                         and ref_table = "sys_file"
                ) as reference_count
                FROM sys_file_metadata
                LEFT JOIN sys_file ON sys_file_metadata . file = sys_file . uid
                WHERE (sys_file.mime_type = "image/jpeg" OR sys_file.mime_type = "image/png")
                AND sys_file_metadata.alternative IS NULL
                ORDER BY reference_count DESC;
            '
        );
        return $records->executeQuery();
    }
}
