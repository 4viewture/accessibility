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
        $result = [
            'iconIdentifier' => 'actions-accessibility',
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_db.xlf:UpdateTextViaAiControl.title'),
            'linkAttributes' => [
                'class' => 'UpdateTextViaAi ',
                'data-uid' => $this->data['vanillaUid'],
                'data-update-text-via-ai-handler' => 'handler',
                'data-table' => $this->data['tableName'],
                'data-field' => $this->data['fieldName'],
                'data-pid' => $pid,
                // Provide the exact input name so the JS can deterministically target the field
                'data-input-name' => sprintf('data[%s][%s][%s]', (string)$this->data['tableName'], (string)$this->data['vanillaUid'], (string)$this->data['fieldName']),
                // i18n labels for the JS wizard UI
                'data-i18n-title' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.wizard.title') ?: 'Generating text via AI…',
                'data-i18n-waiting-prefix' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.wizard.waitingPrefix') ?: 'Waiting for AI result…',
                'data-i18n-seconds-suffix' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.wizard.secondsSuffix') ?: 's',
                'data-i18n-close' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.wizard.close') ?: 'Close',
                'data-i18n-error-describe-missing' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.ajaxRouteMissingDescribe') ?: 'AJAX route not found: accessibility_ai_describe',
                'data-i18n-error-status-result-missing' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.ajaxRouteMissingStatusResult') ?: 'AJAX routes not found for status/result',
                'data-i18n-error-missing-id' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.missingId') ?: 'Missing id from describe',
                'data-i18n-error-timeout' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.timeout') ?: 'The AI did not finish in time. Please try again later.',
                'data-i18n-error-no-result' => (string)$GLOBALS['LANG']->sL('LLL:EXT:accessibility/Resources/Private/Language/locallang_js.xlf:js.error.noResult') ?: 'No result received.',
            ],
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@4viewture/accessibility/UpdateTextViaAiControl.js')
            ],
            'html' => 'tada',
        ];
        return $result;
    }
}
