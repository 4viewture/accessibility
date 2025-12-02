<?php

namespace FourViewture\Accessibility\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

class ContextNormalizationService
{
    public function __construct(
        private readonly ReferenceIndex $referenceIndex,
        private readonly PageRepository $pageRepository
    ) {
    }

    public function resolveContext(int $pid, string $table, int $uid, string $field, File $file): string
    {
        $parts = [
            $this->fetchFromFileObject($file)
        ];

        $text = trim(implode(' – ', array_filter($parts)));
        if ($text === '') {
            $text = 'Image description request for ' . ($table ?: 'record') . ' #' . $uid . ' (' . $field . ')';
        }
        return $text;
    }

    public function fetchFromFileObject(File $file): string
    {
        return 'filename: ' . $file->getName();
    }

    public function fetchFromReferenceIndex(File $file)
    {
        return $this->makeRef('sys_file', $file->getUid());
    }

    /**
     * based on vendor/typo3/cms-backend/Classes/Controller/ContentElement/ElementInformationController.php:536
     */
    protected function makeRef(string $selectTable, int $selectUid): array
    {
        $refLines = [];

        // Files reside in sys_file table
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');

        $predicates = [
            $queryBuilder->expr()->eq(
                'ref_table',
                $queryBuilder->createNamedParameter($selectTable)
            ),
            $queryBuilder->expr()->eq(
                'ref_uid',
                $queryBuilder->createNamedParameter($selectUid, Connection::PARAM_INT)
            ),
        ];

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(...$predicates)
            ->executeQuery()
            ->fetchAllAssociative();

        // Compile information for title tag:
        foreach ($rows as $row) {
            if ($row['tablename'] === 'sys_file_reference') {
                $row = $this->transformFileReferenceToRecordReference($row);
                if ($row === null) {
                    continue;
                }
            }
            $line = [];

            $record = BackendUtility::getRecordWSOL($row['tablename'], $row['recuid']);
            if ($record) {
                $parentRecord = BackendUtility::getRecord('pages', $record['pid']);
                $parentRecordTitle = is_array($parentRecord)
                    ? BackendUtility::getRecordTitle('pages', $parentRecord)
                    : '';
                $line['row'] = $row;
                $line['record'] = $record;
                $line['recordTitle'] = BackendUtility::getRecordTitle($row['tablename'], $record);
                $line['parentRecord'] = $parentRecord;
                $line['parentRecordTitle'] = $parentRecordTitle;
                $line['path'] = BackendUtility::getRecordPath($record['pid'], '', 0, 0);
                $line['pid'] = $record['pid'];
                if ($this->pageRepository->isPageVisi)
                try {
                    $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
                    $site = $siteFinder->getSiteByPageId($record['pid']);
                    $line['siteConfig'] = $site->getConfiguration();
                    $line['uri'] = (string)$site->getRouter()->generateUri((int)$record['pid']);
                } catch (SiteNotFoundException $exception) {
                    // lateron we should skip the record ...
                }
                if (isset($line['uri'])) {
                    $line['textcontent'] = strip_tags(@file_get_contents($line['uri']));
                }

            } else {
                $line['row'] = $row;
            }
            $refLines[] = $line;
        }
        return $refLines;
    }

    /**
     * Convert FAL file reference (sys_file_reference) to reference index (sys_refindex) table format
     */
    protected function transformFileReferenceToRecordReference(array $referenceRecord): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $fileReference = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($referenceRecord['recuid'], Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return $fileReference ? [
            'recuid' => $fileReference['uid_foreign'],
            'tablename' => $fileReference['tablenames'],
            'field' => $fileReference['fieldname'],
            'flexpointer' => '',
            'softref_key' => '',
            'sorting' => $fileReference['sorting_foreign'],
        ] : null;
    }
}
