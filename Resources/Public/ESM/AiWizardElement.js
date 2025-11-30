// AiWizardElement.js
// Custom Element that encapsulates the AI wizard behavior for TYPO3 FormEngine fields

(function () {
  const TAG = 'accessibility-ai-wizard';

  // AJAX URL keys as registered in Configuration/Backend/AjaxRoutes.php
  const AJAX_KEYS = {
    describe: 'accessibility_ai_describe',
    status: 'accessibility_ai_status',
    result: 'accessibility_ai_result'
  };

  const ajaxUrl = (key) => {
    const map = (top.TYPO3 && top.TYPO3.settings && top.TYPO3.settings.ajaxUrls) ||
      (window.TYPO3 && window.TYPO3.settings && window.TYPO3.settings.ajaxUrls) || {};
    return map[key] || '';
  };

  function spinnerMarkup(counterStart = 0, waitingPrefix = 'Waiting for AI result…', secondsSuffix = 's') {
    return `
      <div style="display:flex;align-items:center;gap:12px;">
        <div class=smaller title="${waitingPrefix} ${counterStart}${secondsSuffix}"> <span data-ai-counter>${counterStart}</span>${secondsSuffix}</div>
      </div>
    `;
  }

  function appendQuery(url, key, value) {
    try {
      const u = new URL(url, window.location.origin);
      u.searchParams.set(key, value);
      return u.toString();
    } catch (e) {
      const hasQuery = url.includes('?');
      const sep = hasQuery ? '&' : '?';
      return `${url}${sep}${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
    }
  }

  async function postJson(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
      credentials: 'same-origin'
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error((json && json.error) || `HTTP ${res.status}`);
    return json;
  }

  async function getJson(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error((json && json.error) || `HTTP ${res.status}`);
    return json;
  }

  function findFieldInputNearby(startEl) {
    const wrapper = startEl.closest('.t3js-formengine-field-item, .formengine-field-item, .form-group, .form-section, .formengine-field') || startEl.parentElement;
    if (wrapper) {
      const nameAttr = wrapper.getAttribute('data-formengine-input-name') || wrapper.dataset?.formengineInputName;
      if (nameAttr) {
        const direct = document.querySelector(`textarea[name="${nameAttr}"]`) || document.querySelector(`input[name="${nameAttr}"]`);
        if (direct) return direct;
      }
      const inner = wrapper.querySelector('textarea, input');
      if (inner) return inner;
    }
    return null;
  }

  function findFieldInput(table, uid, field, startEl) {
    if (startEl) {
      const inputName = startEl.getAttribute('data-input-name');
      if (inputName) {
        const byDataAttr = document.querySelector(`[data-formengine-input-name="${inputName}"]`);
        if (byDataAttr) return byDataAttr;
        const directByData = document.querySelector(`textarea[name="${inputName}"]`) || document.querySelector(`input[name="${inputName}"]`);
        if (directByData) return directByData;
      }
    }

    const selectorEsc = (s) => s.replace(/([:\[\].])/g, '\\$1');
    const namePrefix = `data[${table}][${uid}]`;
    const nameA = `${namePrefix}[${field}]`;
    const candidates = [
      `textarea[name="${nameA}"]`,
      `input[name="${nameA}"]`,
      `textarea[name$="[${selectorEsc(field)}]"]`,
      `input[name$="[${selectorEsc(field)}]"]`
    ];
    for (const sel of candidates) {
      const el = document.querySelector(sel);
      if (el) return el;
    }

    const visibleByData = document.querySelector(`[data-formengine-input-name="${nameA}"]`);
    if (visibleByData) return visibleByData;

    return startEl ? findFieldInputNearby(startEl) : null;
  }

  function setValueIntoField(fieldEl, text, startEl) {
    if (fieldEl && 'value' in fieldEl) {
      if (fieldEl.tagName === 'INPUT' && fieldEl.type === 'hidden') {
        const name = fieldEl.getAttribute('name');
        let visible = null;
        if (name) {
          visible = document.querySelector(`[data-formengine-input-name="${name}"]`);
        }
        if (!visible && fieldEl.closest) {
          const wrap = fieldEl.closest('.form-wizards-wrap, .formengine-field-item, .t3js-formengine-field-item, .form-control-wrap');
          if (wrap) {
            visible = wrap.querySelector('input.form-control[data-formengine-input-name], textarea[data-formengine-input-name]') || wrap.querySelector('[data-formengine-input-name]');
          }
        }
        if (visible && 'value' in visible) {
          visible.value = text;
          visible.dispatchEvent(new Event('input', { bubbles: true }));
          visible.dispatchEvent(new Event('change', { bubbles: true }));
          fieldEl.value = text;
          fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
          fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
          return;
        }
      }

      fieldEl.value = text;
      fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
      fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const wrapper = (fieldEl && fieldEl.closest) ? fieldEl.closest('.t3js-formengine-field-item, .formengine-field-item, .form-group, .form-section, .formengine-field') : (startEl ? startEl.closest('.t3js-formengine-field-item, .formengine-field-item, .form-group, .form-section, .formengine-field') : null);
    if (wrapper) {
      const ckRoot = wrapper.querySelector('.ck-editor, .ck-editor__editable, .ck-content');
      if (ckRoot) {
        const ckContent = wrapper.querySelector('.ck-content');
        if (ckContent) {
          ckContent.textContent = text;
        }
        if (fieldEl && 'value' in fieldEl) {
          fieldEl.value = text;
          fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
          fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }

    if (wrapper) {
      const codeMirror = wrapper.querySelector('.CodeMirror');
      if (codeMirror) {
        const textarea = wrapper.querySelector('textarea');
        if (textarea) {
          textarea.value = text;
          textarea.dispatchEvent(new Event('input', { bubbles: true }));
          textarea.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
  }

  function createModal(html) {
    const overlay = document.createElement('div');
    overlay.className = 'ai-modal-overlay';
    const modal = document.createElement('dialog');
    modal.setAttribute('open', '');
    modal.className = 'ai-modal';
    modal.innerHTML = html;
    overlay.appendChild(modal);
    Object.assign(overlay.style, {
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      background: 'rgba(0,0,0,0.3)', zIndex: 99999, display: 'flex', alignItems: 'center', justifyContent: 'center'
    });
    Object.assign(modal.style, {
      background: '#fff', borderRadius: '6px', maxWidth: '400px', width: '90%', padding: '16px',
      boxShadow: '0 10px 30px rgba(0,0,0,0.2)', fontFamily: 'system-ui, sans-serif', border:'none'
    });
    document.body.appendChild(overlay);
    const close = () => overlay.remove();
    return { overlay, modal, close };
  }

  class AccessibilityAiWizard extends HTMLElement {
    connectedCallback() {
      // Optionally render internal UI unless used in silent bootstrap mode
      if (!this.hasAttribute('data-silent')) {
        const imageUri = this.getAttribute('data-image-uri') || '';
        const title = this.dataset.i18nTitle || 'Generating text via AI…';
        // Use light DOM for TYPO3 BE styling inheritance
        this.innerHTML = `
          <button type="button" class="btn btn-default" title="${title}" data-ai-trigger>
            ${imageUri ? `<img src="${imageUri}" alt="AI" style="width:16px;height:16px;vertical-align:middle;">` : 'AI'}
          </button>
        `;
        const trigger = this.querySelector('[data-ai-trigger]');
        if (trigger) {
          trigger.addEventListener('click', () => this.start());
        }
      }
    }

    async start() {
      const table = this.getAttribute('data-table') || '';
      const field = this.getAttribute('data-field') || '';
      const uid = this.getAttribute('data-uid') || '';
      const pid = parseInt(this.getAttribute('data-pid') || '0', 10) || 0;
      const thinkingImage = this.getAttribute('data-thinking-image-uri') || null;

      const i18n = {
        title: this.dataset.i18nTitle || 'Generating text via AI…',
        waitingPrefix: this.dataset.i18nWaitingPrefix || 'Waiting for AI result…',
        secondsSuffix: this.dataset.i18nSecondsSuffix || 's',
        close: this.dataset.i18nClose || 'Close',
        errDescribeMissing: this.dataset.i18nErrorDescribeMissing || 'AJAX route not found: accessibility_ai_describe',
        errStatusResultMissing: this.dataset.i18nErrorStatusResultMissing || 'AJAX routes not found for status/result',
        errMissingId: this.dataset.i18nErrorMissingId || 'Missing id from describe',
        errTimeout: this.dataset.i18nErrorTimeout || 'The AI did not finish in time. Please try again later.',
        errNoResult: this.dataset.i18nErrorNoResult || 'No result received.',
      };

      const fieldInput = findFieldInput(table, uid, field, this);

      let imageHtml = '';
      if (thinkingImage) {
        imageHtml = `<div class="d-flex justify-content-center"><img src="${thinkingImage}" alt="Thinking" style="width:100%;max-width:250px;vertical-align:middle;"></div>`;
      }

      const modal = createModal(`
        <h3 style="border: 0.2rem solid #5ee0ff; border-radius: 2rem" class="mb-0 mt-2 p-3">${i18n.title}</h3>
        <div style="display:flex;flex-direction:column;gap:8px;">
          ${imageHtml}
          <div class="d-flex justify-content-center"><div data-ai-progress style="display: inline-block">${spinnerMarkup(0, i18n.waitingPrefix, i18n.secondsSuffix)}</div></div>
          <div class="d-flex justify-content-center"><div data-ai-message style="color:#a00;display: inline-block;"></div></div>
          <div class="d-flex justify-content-center">
            <button data-ai-cancel type="button" class="btn btn-primary">${i18n.close}</button>
          </div>
        </div>
      `);

      const $ = (sel) => modal.modal.querySelector(sel);
      $('button[data-ai-cancel]').addEventListener('click', modal.close);

      const describeUrl = ajaxUrl(AJAX_KEYS.describe);
      const msg = $('[data-ai-message]');
      if (!describeUrl) {
        msg.textContent = i18n.errDescribeMissing;
        return;
      }

      let counter = 0;
      const counterEl = modal.modal.querySelector('[data-ai-counter]');
      const counterTimer = setInterval(() => {
        counter += 1;
        if (counterEl) counterEl.textContent = String(counter);
      }, 1000);

      try {
        const { id } = await postJson(describeUrl, { pid, table, uid, field });
        if (!id) throw new Error(i18n.errMissingId);
        this.dataset.aiLastId = id;

        const statusBaseUrl = ajaxUrl(AJAX_KEYS.status);
        const resultBaseUrl = ajaxUrl(AJAX_KEYS.result);
        if (!statusBaseUrl || !resultBaseUrl) {
          throw new Error(i18n.errStatusResultMissing);
        }

        let processed = false;
        for (let i = 0; i < 15; i++) {
          const url = appendQuery(statusBaseUrl, 'id', id);
          const statusJson = await getJson(url);
          if (statusJson && statusJson.processed) { processed = true; break; }
          await new Promise(r => setTimeout(r, 1000));
        }
        if (!processed) {
          throw new Error(i18n.errTimeout);
        }

        const resultJson = await getJson(appendQuery(resultBaseUrl, 'id', id));
        const text = (resultJson && resultJson.result) || '';
        if (!text) throw new Error(i18n.errNoResult);

        if (fieldInput) {
          setValueIntoField(fieldInput, text, this);
        } else {
          const near = findFieldInputNearby(this);
          if (near) {
            setValueIntoField(near, text, this);
          } else {
            const focused = document.activeElement;
            if (focused && (focused.tagName === 'TEXTAREA' || focused.tagName === 'INPUT')) {
              setValueIntoField(focused, text, this);
            }
          }
        }

        modal.close();
      } catch (e) {
        msg.textContent = (e && e.message) ? e.message : String(e);
      } finally {
        clearInterval(counterTimer);
        const progress = $('[data-ai-progress]');
        if (progress) progress.style.display = 'none';
        // If this element was inserted just for programmatic use, remove it
        if (this.hasAttribute('data-silent')) {
          // Delay a tick to ensure modal is fully removed
          setTimeout(() => { try { this.remove(); } catch (e) {} }, 0);
        }
      }
    }
  }

  if (!customElements.get(TAG)) {
    customElements.define(TAG, AccessibilityAiWizard);
  }

  // Bootstrap: support classic FormEngine link-based FieldControl
  function initLinkBootstrap() {
    const selector = '[data-update-text-via-ai-handler]';
    const attach = (el) => {
      if (el._aiWizardBound) return; // avoid duplicate binding
      el._aiWizardBound = true;
      el.addEventListener('click', (event) => {
        event.preventDefault();
        const link = event.currentTarget;
        // Create the custom element next to the link for proximity-based field lookup
        const elWizard = document.createElement(TAG);
        elWizard.setAttribute('data-silent', '1');
        // Copy all data-* attributes
        for (const attr of link.attributes) {
          if (attr.name.startsWith('data-')) {
            elWizard.setAttribute(attr.name, attr.value);
          }
        }
        // Insert right after the link to keep DOM proximity
        if (link.parentNode) {
          if (link.nextSibling) {
            link.parentNode.insertBefore(elWizard, link.nextSibling);
          } else {
            link.parentNode.appendChild(elWizard);
          }
        } else {
          document.body.appendChild(elWizard);
        }
        // Start the wizard flow
        if (typeof elWizard.start === 'function') {
          elWizard.start();
        }
      });
    };

    // Initial bind
    document.querySelectorAll(selector).forEach(attach);
    // Observe future additions (IRRE rows etc.)
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        for (const node of m.addedNodes) {
          if (!(node instanceof Element)) continue;
          if (node.matches && node.matches(selector)) {
            attach(node);
          }
          node.querySelectorAll && node.querySelectorAll(selector).forEach(attach);
        }
      }
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLinkBootstrap);
  } else {
    initLinkBootstrap();
  }
})();
