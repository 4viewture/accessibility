<?php

declare(strict_types=1);

namespace FourViewture\Accessibility\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileAccessNormalizationService
{
    public function resolveOriginalFile(string $table, int $uid, string $field): ?File
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

        $references = $fileRepository->findByRelation($table, $field, $uid);
        if (!empty($references)) {
            $ref = $references[0];
            return $ref->getOriginalFile();
        }

        if ($table === 'sys_file_metadata' && $uid > 0) {
            // Special handling: metadata row points to sys_file via 'file'
            $meta = BackendUtility::getRecord('sys_file_metadata', $uid, 'file,title,alternative');
            if ($meta && !empty($meta['file'])) {
                return $fileRepository->findByUid($meta['file']);
            }
        }

        // Case B: if table is sys_file and uid points to a file
        if ($table === 'sys_file' && $uid > 0) {
            return $fileRepository->findByUid($uid);
        }

        return null;
    }

    public function resolveImageUrl(?File $original = null, bool $embedImage = false): string
    {
        if ($original === null) {
            return '';
        }

        if ($embedImage) {
            return 'data:' . $original->getMimeType() . ';base64,' . base64_encode($original->getContents());
        }

        if (is_string($url) && $url !== '') {
            $url = $original->getPublicUrl();
            return $this->absoluteUrl($url);
        }
    }

    private function absoluteUrl(string $url): string
    {
        // If already absolute, return as-is
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        // Build absolute from current backend host
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            return rtrim($scheme . '://' . $host, '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }
}
