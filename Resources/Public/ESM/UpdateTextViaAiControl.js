// UpdateTextViaAiControl.js
// Backend form control that opens a small wizard to call the TYPO3-proxied AI API
// The proxy injects an API token from PageTS or user TSconfig.

(function () {
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

  function createModal(html) {
    // Minimal modal implementation to avoid hard dependency on TYPO3 Modal
    const overlay = document.createElement('div');
    overlay.className = 'ai-modal-overlay';
    const modal = document.createElement('div');
    modal.className = 'ai-modal';
    modal.innerHTML = html;
    overlay.appendChild(modal);
    Object.assign(overlay.style, {
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      background: 'rgba(0,0,0,0.3)', zIndex: 99999, display: 'flex', alignItems: 'center', justifyContent: 'center'
    });
    Object.assign(modal.style, {
      background: '#fff', borderRadius: '6px', maxWidth: '640px', width: '90%', padding: '16px',
      boxShadow: '0 10px 30px rgba(0,0,0,0.2)', fontFamily: 'system-ui, sans-serif'
    });
    document.body.appendChild(overlay);
    const close = () => overlay.remove();
    return { overlay, modal, close };
  }

  function spinnerMarkup(counterStart = 0) {
    return `
      <div style="display:flex;align-items:center;gap:12px;margin-top:8px;">
        <div class="lds-dual-ring"></div>
        <div>Waiting for AI result… <span data-ai-counter>${counterStart}</span>s</div>
      </div>
      <style>
        .lds-dual-ring {
          display: inline-block;
          width: 24px; height: 24px;
        }
        .lds-dual-ring:after {
          content: " "; display: block; width: 24px; height: 24px;
          margin: 1px; border-radius: 50%;
          border: 2px solid #999; border-color: #999 transparent #999 transparent;
          animation: lds-dual-ring 1.2s linear infinite;
        }
        @keyframes lds-dual-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg);} }
      </style>
    `;
  }

  function findFieldInputNearby(startEl) {
    // Try to locate a related input/textarea within the same FormEngine field wrapper
    const wrapper = startEl.closest('.t3js-formengine-field-item, .formengine-field-item, .form-group, .form-section, .formengine-field') || startEl.parentElement;
    if (wrapper) {
      // If TYPO3 exposes the exact input name on the wrapper, use it to find the field
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
    // 0) Deterministic: prefer the exact input name provided on the button
    if (startEl) {
      const inputName = startEl.getAttribute('data-input-name');
      if (inputName) {
        // First try: TYPO3 visible field using data-formengine-input-name (no name attr)
        const byDataAttr = document.querySelector(`[data-formengine-input-name="${inputName}"]`);
        if (byDataAttr) return byDataAttr;
        // Fallback to real name (usually the hidden input)
        const directByData = document.querySelector(`textarea[name="${inputName}"]`) || document.querySelector(`input[name="${inputName}"]`);
        if (directByData) return directByData;
      }
    }

    // Try typical BE form input/textarea names (exact match first)
    const selectorEsc = (s) => s.replace(/([:\[\].])/g, '\\$1');
    const namePrefix = `data[${table}][${uid}]`;
    const nameA = `${namePrefix}[${field}]`;
    const candidates = [
      `textarea[name="${nameA}"]`,
      `input[name="${nameA}"]`,
      // Fallbacks: search by field suffix within the same record
      `textarea[name$="[${selectorEsc(field)}]"]`,
      `input[name$="[${selectorEsc(field)}]"]`
    ];
    for (const sel of candidates) {
      const el = document.querySelector(sel);
      if (el) return el;
    }

    // Try: visible FormEngine input using data-formengine-input-name (without name attr)
    const visibleByData = document.querySelector(`[data-formengine-input-name="${nameA}"]`);
    if (visibleByData) return visibleByData;

    // Proximity-based fallback: look near the clicked control
    return startEl ? findFieldInputNearby(startEl) : null;
  }

  function setValueIntoField(fieldEl, text, startEl) {
    // Basic case: plain input/textarea
    if (fieldEl && 'value' in fieldEl) {
      // Special handling: if we targeted the hidden input (name=...), update the visible peer instead
      if (fieldEl.tagName === 'INPUT' && fieldEl.type === 'hidden') {
        const name = fieldEl.getAttribute('name');
        let visible = null;
        if (name) {
          // Look for the corresponding visible input with data-formengine-input-name
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
          // Keep hidden in sync too
          fieldEl.value = text;
          fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
          fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
          return;
        }
      }

      // Default: update the element itself
      fieldEl.value = text;
      fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
      fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Try to update CKEditor 5 if present in the same wrapper
    const wrapper = (fieldEl && fieldEl.closest) ? fieldEl.closest('.t3js-formengine-field-item, .formengine-field-item, .form-group, .form-section, .formengine-field') : (startEl ? startEl.closest('.t3js-formengine-field-item, .formengine-field-item, .form-group, .form-section, .formengine-field') : null);
    if (wrapper) {
      const ckRoot = wrapper.querySelector('.ck-editor, .ck-editor__editable, .ck-content');
      if (ckRoot) {
        // Prefer setting the hidden textarea (fieldEl) and also mirror into the visible content
        const ckContent = wrapper.querySelector('.ck-content');
        if (ckContent) {
          // Insert plain text (escape by using textContent)
          ckContent.textContent = text;
        }
        // Fire events once more to allow TYPO3 RTE glue to sync if applicable
        if (fieldEl && 'value' in fieldEl) {
          fieldEl.value = text;
          fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
          fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }

    // Try T3Editor/CodeMirror (source code editors)
    if (wrapper) {
      const codeMirror = wrapper.querySelector('.CodeMirror');
      if (codeMirror) {
        // Update underlying textarea as a fallback; CodeMirror typically syncs on events
        const textarea = wrapper.querySelector('textarea');
        if (textarea) {
          textarea.value = text;
          textarea.dispatchEvent(new Event('input', { bubbles: true }));
          textarea.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
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

  function appendQuery(url, key, value) {
    try {
      const u = new URL(url, window.location.origin);
      u.searchParams.set(key, value);
      return u.toString();
    } catch (e) {
      // Fallback for relative URLs without base in older browsers
      const hasQuery = url.includes('?');
      const sep = hasQuery ? '&' : '?';
      return `${url}${sep}${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
    }
  }

  function onClick(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    const table = btn.getAttribute('data-table') || '';
    const field = btn.getAttribute('data-field') || '';
    const uid = btn.getAttribute('data-uid') || '';
    const pid = parseInt(btn.getAttribute('data-pid') || '0', 10) || 0;

    const fieldInput = findFieldInput(table, uid, field, btn);

    const modal = createModal(`
      <h3 style="margin-top:0;margin-bottom:8px;">Generate text via AI</h3>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <label>Image URL <input type="url" data-ai-image-url placeholder="https://..." style="width:100%" /></label>
        <label>Context <input type="text" data-ai-context placeholder="What is this used for?" style="width:100%" /></label>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
          <button data-ai-cancel type="button">Cancel</button>
          <button data-ai-start type="button" class="btn btn-primary">Start</button>
        </div>
        <div data-ai-progress style="display:none;">${spinnerMarkup(0)}</div>
        <div data-ai-message style="color:#a00;"></div>
      </div>
    `);

    const $ = (sel) => modal.modal.querySelector(sel);

    $('button[data-ai-cancel]').addEventListener('click', modal.close);
    $('button[data-ai-start]').addEventListener('click', async () => {
      const imageUrl = ($('input[data-ai-image-url]').value || '').trim();
      const context = ($('input[data-ai-context]').value || '').trim();
      const msg = $('[data-ai-message]');
      msg.textContent = '';
      if (!imageUrl || !context) {
        msg.textContent = 'Please provide both image URL and context.';
        return;
      }
      const describeUrl = ajaxUrl(AJAX_KEYS.describe);
      if (!describeUrl) {
        msg.textContent = 'AJAX route not found: accessibility_ai_describe';
        return;
      }

      $('[data-ai-progress]').style.display = 'block';
      let counter = 0;
      const counterEl = modal.modal.querySelector('[data-ai-counter]');
      const counterTimer = setInterval(() => {
        counter += 1;
        if (counterEl) counterEl.textContent = String(counter);
      }, 1000);

      try {
        // Step 1: POST /describe via proxy
        // Also send table, uid and field as requested so the backend/proxy can use it later
        const { id } = await postJson(describeUrl, { imageUrl, context, pid, table, uid, field });
        if (!id) throw new Error('Missing id from describe');

        // Store id in the webcomponent/modal for later requests
        modal.modal.dataset.aiId = id;
        btn.dataset.aiLastId = id;

        // Step 2: poll /status up to 15 times (1s)
        const statusBaseUrl = ajaxUrl(AJAX_KEYS.status);
        const resultBaseUrl = ajaxUrl(AJAX_KEYS.result);
        if (!statusBaseUrl || !resultBaseUrl) {
          throw new Error('AJAX routes not found for status/result');
        }

        let processed = false;
        for (let i = 0; i < 15; i++) {
          const url = appendQuery(statusBaseUrl, 'id', id);
          const statusJson = await getJson(url);
          if (statusJson && statusJson.processed) { processed = true; break; }
          await new Promise(r => setTimeout(r, 1000));
        }
        if (!processed) {
          throw new Error('The AI did not finish in time. Please try again later.');
        }

        // Step 3: fetch result
        const resultJson = await getJson(appendQuery(resultBaseUrl, 'id', id));
        const text = (resultJson && resultJson.result) || '';
        if (!text) throw new Error('No result received.');

        // Step 4: write into field
        if (fieldInput) {
          setValueIntoField(fieldInput, text, btn);
        } else {
          // Fallback: try to find a nearby field relative to the button again
          const near = findFieldInputNearby(btn);
          if (near) {
            setValueIntoField(near, text, btn);
          } else {
            // Last resort: the currently focused input/textarea
            const focused = document.activeElement;
            if (focused && (focused.tagName === 'TEXTAREA' || focused.tagName === 'INPUT')) {
              setValueIntoField(focused, text, btn);
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
      }
    });
  }

  function init() {
    const buttons = document.querySelectorAll('[data-update-text-via-ai-handler]');
    buttons.forEach((btn) => {
      btn.addEventListener('click', onClick);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
