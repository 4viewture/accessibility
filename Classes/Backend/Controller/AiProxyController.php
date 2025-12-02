<?php

declare(strict_types=1);

namespace FourViewture\Accessibility\Backend\Controller;

use FourViewture\Accessibility\Service\ContextNormalizationService;
use FourViewture\Accessibility\Service\FileAccessNormalizationService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\FileRepository;

class AiProxyController
{
    private const BASE_URL = 'https://ai.api.4viewture.eu/api/v1';

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly FileAccessNormalizationService $fileAccessNormalizationService,
        private readonly ContextNormalizationService $contextNormalizationService
    ) {
    }

    public function describe(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->getJsonBody($request);
        $pid = (int)($data['pid'] ?? 0);
        $table = (string)($data['table'] ?? '');
        $field = (string)($data['field'] ?? '');
        $uid = (int)($data['uid'] ?? 0);

        // Derive imageUrl and context on the server based on table/uid/field
        try {
            /** @var ExtensionConfiguration $extConf */
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class);
            $embedImage = (bool)$extConf->get('accessibility', 'embedImageForApi');

            [$imageUrl, $context] = $this->deriveDescribePayload($pid, $table, $uid, $field, $embedImage);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Could not derive image/context: ' . $e->getMessage()], 400);
        }
        if ($imageUrl === '' || $context === '') {
            return $this->json(['error' => 'Missing imageUrl or context after derivation'], 400);
        }

        $token = $this->resolveToken($pid);
        if ($token === null || $token === '') {
            return $this->json(['error' => 'Missing API token in PageTS or user settings'], 400);
        }

        try {
            $response = $this->requestFactory->request(
                self::BASE_URL . '/describe',
                'POST',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-Account-Token' => $token,
                    ],
                    'body' => json_encode(['imageUrl' => $imageUrl, 'context' => $context], JSON_THROW_ON_ERROR),
                    'timeout' => 15,
                ]
            );
            $payload = $this->decodeJsonResponse($response);
            return $this->json($payload, $response->getStatusCode());
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Proxy error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Derive the external API payload (imageUrl, context) from TYPO3 context
     */
    private function deriveDescribePayload(int $pid, string $table, int $uid, string $field, bool $embedImage): array
    {
        $originalFile = $this->fileAccessNormalizationService->resolveOriginalFile($table, $uid, $field);

        $context = '';

        // 1) Try to resolve image URL
        $imageUrl = $this->fileAccessNormalizationService->resolveImageUrl($originalFile, $embedImage);

        // 2) Derive context: table/field label + record title + page title if available
        $context = $this->contextNormalizationService->resolveContext($pid, $table, $uid, $field, $originalFile);

        if ($imageUrl === '' || $context === '') {
            // Provide helpful details for debugging
            $parts = [];
            if ($imageUrl === '') { $parts[] = 'imageUrl'; }
            if ($context === '') { $parts[] = 'context'; }
            throw new \RuntimeException('Unable to determine: ' . implode(', ', $parts));
        }

        return [$imageUrl, $context];
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        // Accept id from path attribute or from query parameter (?id=...)
        $id = (string)($request->getAttribute('id') ?? '');
        if ($id === '') {
            $queryParams = $request->getQueryParams();
            $id = (string)($queryParams['id'] ?? '');
        }
        if ($id === '') {
            return $this->json(['error' => 'Missing id'], 400);
        }
        try {
            $response = $this->requestFactory->request(self::BASE_URL . '/status/' . rawurlencode($id), 'GET', [
                'timeout' => 15,
            ]);
            $payload = $this->decodeJsonResponse($response);
            return $this->json($payload, $response->getStatusCode());
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Proxy error: ' . $e->getMessage()], 500);
        }
    }

    public function result(ServerRequestInterface $request): ResponseInterface
    {
        // Accept id from path attribute or from query parameter (?id=...)
        $id = (string)($request->getAttribute('id') ?? '');
        if ($id === '') {
            $queryParams = $request->getQueryParams();
            $id = (string)($queryParams['id'] ?? '');
        }
        if ($id === '') {
            return $this->json(['error' => 'Missing id'], 400);
        }
        try {
            $response = $this->requestFactory->request(self::BASE_URL . '/result/' . rawurlencode($id), 'GET', [
                'timeout' => 15,
            ]);
            $payload = $this->decodeJsonResponse($response);
            return $this->json($payload, $response->getStatusCode());
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Proxy error: ' . $e->getMessage()], 500);
        }
    }

    private function resolveToken(int $pid = 0): ?string
    {
        // Priority 1: PageTSConfig (if pid provided)
        if ($pid > 0) {
            $pageTs = BackendUtility::getPagesTSconfig($pid);
            $token = $pageTs['accessibility.']['ai.']['token'] ?? $pageTs['tx_accessibility.']['ai.']['token'] ?? null;
            if (!empty($token) && is_string($token)) {
                return trim($token);
            }
        }

        // Priority 2: User TSconfig
        $beUser = $this->getBackendUser();
        if ($beUser) {
            $userTs = $beUser->getTSConfig();
            $token = $userTs['accessibility.']['ai.']['token'] ?? $userTs['tx_accessibility.']['ai.']['token'] ?? null;
            if (!empty($token) && is_string($token)) {
                return trim($token);
            }
        }

        /** @var ExtensionConfiguration $extConf */
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $key = $extConf->get('accessibility', 'apiKey');
        if (!empty($key)) {
            return trim($key);
        }

        return null;
    }

    private function getJsonBody(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed) && !empty($parsed)) {
            return $parsed;
        }
        $body = (string)$request->getBody();
        if ($body !== '') {
            try {
                return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
            }
        }
        return [];
    }

    private function decodeJsonResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $contents = (string)$response->getBody();
        try {
            return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return ['raw' => $contents];
        }
    }

    private function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
