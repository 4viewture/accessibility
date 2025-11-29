<?php

declare(strict_types=1);

namespace FourViewture\Accessibility\FormEngine\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdateTextViaAiControl extends AbstractNode
{
    public function render(): array
    {
        $pid = 0;
        if (isset($this->data['effectivePid']) && is_int($this->data['effectivePid'])) {
            $pid = (int)$this->data['effectivePid'];
        } elseif (isset($this->data['databaseRow']['pid'])) {
            $pid = (int)$this->data['databaseRow']['pid'];
        }
        $imagePath = \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:accessibility/Resources/Public/Icons/robot1.svg');

        // Prepare i18n labels
        $randThinking = rand(1, 50);
        $labelTitle = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_thinking.xlf:thinking_' . $randThinking) ?: 'Generating text via AI…');
        $labelWaiting = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.wizard.waitingPrefix') ?: 'Waiting for AI result…');
        $labelSeconds = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.wizard.secondsSuffix') ?: 's');
        $labelClose = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.wizard.close') ?: 'Close');
        $errDescribeMissing = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.ajaxRouteMissingDescribe') ?: 'AJAX route not found: accessibility_ai_describe');
        $errStatusResultMissing = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.ajaxRouteMissingStatusResult') ?: 'AJAX routes not found for status/result');
        $errMissingId = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.missingId') ?: 'Missing id from describe');
        $errTimeout = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.timeout') ?: 'The AI did not finish in time. Please try again later.');
        $errNoResult = (string)($GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.noResult') ?: 'No result received.');

        // Build link attributes as required by FormEngine FieldControls
        $linkAttributes = [
            'href' => '#',
            'class' => 'UpdateTextViaAi',
            'data-update-text-via-ai-handler' => '1',
            'data-table' => (string)$this->data['tableName'],
            'data-uid' => (string)$this->data['vanillaUid'],
            'data-field' => (string)$this->data['fieldName'],
            'data-pid' => (string)$pid,
            'data-input-name' => sprintf('data[%s][%s][%s]', (string)$this->data['tableName'], (string)$this->data['vanillaUid'], (string)$this->data['fieldName']),
            'data-i18n-title' => $labelTitle,
            'data-i18n-waiting-prefix' => $labelWaiting,
            'data-i18n-seconds-suffix' => $labelSeconds,
            'data-i18n-close' => $labelClose,
            'data-i18n-error-describe-missing' => $errDescribeMissing,
            'data-i18n-error-status-result-missing' => $errStatusResultMissing,
            'data-i18n-error-missing-id' => $errMissingId,
            'data-i18n-error-timeout' => $errTimeout,
            'data-i18n-error-no-result' => $errNoResult,
            'data-thinking-image-uri' => (string)$imagePath,
            'aria-label' => $labelTitle,
            'title' => $labelTitle,
        ];

        $result = [
            'iconIdentifier' => 'actions-accessibility',
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:UpdateTextViaAiControl.title'),
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@4viewture/accessibility/AiWizardElement.js')
            ],
            'linkAttributes' => $linkAttributes,
        ];
        return $result;
    }
}
