(function () {
  'use strict';

  var config = window.SCBDLiteBuilder || null;
  if (!config || !config.postId || !config.restUrl || !config.nonce) return;

  var modal = null;
  var form = null;
  var status = null;
  var loaded = false;
  var activeRequest = null;

  function text(key) {
    return (config.strings && config.strings[key]) || key;
  }

  function createLauncher() {
    if (document.querySelector('.scbd-lite-floating-launcher')) return;

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'scbd-lite-floating-launcher';
    button.textContent = text('button');
    button.setAttribute('aria-haspopup', 'dialog');
    button.addEventListener('click', function (event) {
      event.preventDefault();
      openModal();
    });
    document.body.appendChild(button);

    var adminBarLink = document.querySelector('#wp-admin-bar-scbd-lite a');
    if (adminBarLink) {
      adminBarLink.setAttribute('href', '#scbd-lite-open');
      adminBarLink.addEventListener('click', function (event) {
        event.preventDefault();
        openModal();
      });
    }
  }

  function buildModal() {
    if (modal) return;

    modal = document.createElement('div');
    modal.className = 'scbd-lite-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'scbd-lite-modal-title');
    modal.hidden = true;

    modal.innerHTML = '' +
      '<div class="scbd-lite-modal-backdrop" data-scbd-lite-close></div>' +
      '<div class="scbd-lite-modal-panel" tabindex="-1">' +
        '<div class="scbd-lite-modal-header">' +
          '<div>' +
            '<h2 id="scbd-lite-modal-title">' + escapeHtml(text('title')) + '</h2>' +
            '<p>' + escapeHtml(text('description')) + '</p>' +
          '</div>' +
          '<button type="button" class="scbd-lite-modal-close" aria-label="' + escapeHtml(text('close')) + '" data-scbd-lite-close>×</button>' +
        '</div>' +
        '<form class="scbd-lite-modal-form">' +
          '<div class="scbd-lite-modal-fields"><p class="scbd-lite-loading">' + escapeHtml(text('loading')) + '</p></div>' +
          '<div class="scbd-lite-modal-footer">' +
            '<span class="scbd-lite-modal-status" aria-live="polite"></span>' +
            '<button type="button" class="scbd-lite-secondary" data-scbd-lite-close>' + escapeHtml(text('close')) + '</button>' +
            '<button type="submit" class="scbd-lite-primary">' + escapeHtml(text('save')) + '</button>' +
          '</div>' +
        '</form>' +
      '</div>';

    document.body.appendChild(modal);
    form = modal.querySelector('form');
    status = modal.querySelector('.scbd-lite-modal-status');

    modal.addEventListener('click', function (event) {
      if (event.target && event.target.hasAttribute('data-scbd-lite-close')) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal && !modal.hidden) {
        closeModal();
      }
    });

    form.addEventListener('submit', saveValues);
  }

  function openModal() {
    buildModal();
    modal.hidden = false;
    document.documentElement.classList.add('scbd-lite-modal-open');
    var panel = modal.querySelector('.scbd-lite-modal-panel');
    if (panel) panel.focus();
    if (!loaded) loadValues();
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.documentElement.classList.remove('scbd-lite-modal-open');
  }

  function loadValues() {
    setStatus(text('loading'));
    request('GET')
      .then(function (data) {
        renderFields(data.fields || [], data.values || {});
        loaded = true;
        setStatus('');
      })
      .catch(function () {
        renderError();
        setStatus(text('error'));
      });
  }

  function saveValues(event) {
    event.preventDefault();
    if (!form) return;

    var submit = form.querySelector('button[type="submit"]');
    var values = collectValues();
    setStatus(text('saving'));
    if (submit) submit.disabled = true;

    request('POST', { values: values })
      .then(function (data) {
        renderFields(data.fields || (activeRequest && activeRequest.fields) || getRenderedFields(), data.values || values);
        setStatus(text('saved'));
      })
      .catch(function () {
        setStatus(text('error'));
      })
      .finally(function () {
        if (submit) submit.disabled = false;
      });
  }

  function request(method, body) {
    var options = {
      method: method,
      credentials: 'same-origin',
      headers: {
        'X-WP-Nonce': config.nonce
      }
    };

    if (body) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(body);
    }

    return fetch(config.restUrl, options).then(function (response) {
      if (!response.ok) throw new Error('Request failed');
      return response.json();
    });
  }

  function renderFields(fields, values) {
    var wrapper = modal.querySelector('.scbd-lite-modal-fields');
    if (!wrapper) return;
    activeRequest = { fields: fields };

    wrapper.innerHTML = '';
    fields.forEach(function (field) {
      var id = 'scbd_lite_modal_' + field.key;
      var row = document.createElement('label');
      row.className = 'scbd-lite-modal-field scbd-lite-modal-field-' + field.key;
      row.setAttribute('for', id);

      var label = document.createElement('span');
      label.textContent = field.label;
      row.appendChild(label);

      var control;
      if (field.type === 'textarea') {
        control = document.createElement('textarea');
        control.rows = 3;
      } else {
        control = document.createElement('input');
        control.type = field.type || 'text';
      }
      control.id = id;
      control.name = field.key;
      control.value = values[field.key] || '';
      control.placeholder = field.placeholder || '';
      control.setAttribute('data-scbd-lite-key', field.key);
      row.appendChild(control);

      if (field.type === 'textarea') {
        var counter = document.createElement('small');
        counter.className = 'scbd-lite-modal-counter';
        row.appendChild(counter);
        updateCounter(control, counter);
        control.addEventListener('input', function () { updateCounter(control, counter); });
      }

      wrapper.appendChild(row);
    });
  }

  function renderError() {
    var wrapper = modal.querySelector('.scbd-lite-modal-fields');
    if (!wrapper) return;
    wrapper.innerHTML = '<p class="scbd-lite-error">' + escapeHtml(text('error')) + '</p>';
  }

  function collectValues() {
    var values = {};
    form.querySelectorAll('[data-scbd-lite-key]').forEach(function (field) {
      values[field.getAttribute('data-scbd-lite-key')] = field.value;
    });
    return values;
  }

  function getRenderedFields() {
    var fields = [];
    form.querySelectorAll('[data-scbd-lite-key]').forEach(function (field) {
      fields.push({
        key: field.getAttribute('data-scbd-lite-key'),
        label: field.closest('label') ? field.closest('label').querySelector('span').textContent : field.name,
        type: field.tagName.toLowerCase() === 'textarea' ? 'textarea' : field.type,
        placeholder: field.placeholder || ''
      });
    });
    return fields;
  }

  function updateCounter(field, counter) {
    counter.textContent = 'Characters: ' + field.value.length;
  }

  function setStatus(message) {
    if (status) status.textContent = message || '';
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"]/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char];
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createLauncher);
  } else {
    createLauncher();
  }
})();
