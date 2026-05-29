(function () {
  'use strict';

  var config = window.SCBDLiteBuilder || null;
  if (!config || !config.postId || !config.restUrl || !config.nonce) {
    return;
  }

  var STORAGE_KEY = 'scbdLiteLauncherPosition:' + config.postId;
  var DRAG_THRESHOLD = 6;
  var DEFAULT_MARGIN = 18;
  var modal = null;
  var form = null;
  var status = null;
  var launcher = null;
  var loaded = false;
  var activeFields = [];
  var pointerState = null;

  function isDiviBuilderParentShell() {
    if (window.self !== window.top) {
      return false;
    }

    var search = window.location.search || '';
    if (/[?&]et_fb=1(?:&|$)/.test(search)) {
      return true;
    }

    var body = document.body;
    var html = document.documentElement;
    return !!(
      (body && (body.classList.contains('et-fb') || body.classList.contains('et-db'))) ||
      (html && html.classList.contains('et-fb-root-ancestor'))
    );
  }

  function bindParentAdminBarBridge() {
    bindAdminBarLink(function () {
      var frames = document.querySelectorAll('iframe');
      frames.forEach(function (frame) {
        try {
          frame.contentWindow.postMessage({ type: 'scbd-lite-open' }, window.location.origin);
        } catch (error) {}
      });
    });
  }

  function text(key) {
    return (config.strings && config.strings[key]) || key;
  }

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  function createLauncher() {
    launcher = document.querySelector('.scbd-lite-floating-launcher');

    if (!launcher) {
      launcher = document.createElement('button');
      launcher.type = 'button';
      launcher.className = 'scbd-lite-floating-launcher';
      launcher.textContent = text('button');
      launcher.setAttribute('aria-haspopup', 'dialog');
      launcher.setAttribute('aria-controls', 'scbd-lite-modal');
      launcher.setAttribute('title', text('button'));
      document.body.appendChild(launcher);
    }

    restoreLauncherPosition();
    bindLauncherEvents();
    bindAdminBarLink();
    window.SCBDLiteOpen = openModal;
  }

  function bindLauncherEvents() {
    launcher.addEventListener('pointerdown', onPointerDown);
    launcher.addEventListener('click', onLauncherClick);
    launcher.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        openModal();
      }
    });
  }

  function onLauncherClick(event) {
    event.preventDefault();
    event.stopPropagation();

    if (pointerState && pointerState.wasDragged) {
      pointerState = null;
      return;
    }

    openModal();
  }

  function onPointerDown(event) {
    if (event.button !== 0) return;

    var rect = launcher.getBoundingClientRect();
    pointerState = {
      id: event.pointerId,
      startX: event.clientX,
      startY: event.clientY,
      left: rect.left,
      top: rect.top,
      width: rect.width,
      height: rect.height,
      wasDragged: false
    };

    launcher.setPointerCapture(event.pointerId);
    launcher.classList.add('is-dragging');
    launcher.addEventListener('pointermove', onPointerMove);
    launcher.addEventListener('pointerup', onPointerUp);
    launcher.addEventListener('pointercancel', onPointerUp);
  }

  function onPointerMove(event) {
    if (!pointerState || event.pointerId !== pointerState.id) return;

    var dx = event.clientX - pointerState.startX;
    var dy = event.clientY - pointerState.startY;

    if (Math.abs(dx) > DRAG_THRESHOLD || Math.abs(dy) > DRAG_THRESHOLD) {
      pointerState.wasDragged = true;
    }

    if (!pointerState.wasDragged) return;

    event.preventDefault();
    setLauncherPosition(pointerState.left + dx, pointerState.top + dy, true);
  }

  function onPointerUp(event) {
    if (!pointerState || event.pointerId !== pointerState.id) return;

    launcher.releasePointerCapture(event.pointerId);
    launcher.classList.remove('is-dragging');
    launcher.removeEventListener('pointermove', onPointerMove);
    launcher.removeEventListener('pointerup', onPointerUp);
    launcher.removeEventListener('pointercancel', onPointerUp);

    if (pointerState.wasDragged) {
      saveLauncherPosition();
    }

    // Leave pointerState in place until click fires, so the synthetic click after drag is suppressed.
    window.setTimeout(function () {
      pointerState = null;
    }, 0);
  }

  function restoreLauncherPosition() {
    var saved = null;

    try {
      saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null');
    } catch (error) {
      saved = null;
    }

    if (saved && typeof saved.left === 'number' && typeof saved.top === 'number') {
      setLauncherPosition(saved.left, saved.top, false);
      return;
    }

    window.requestAnimationFrame(function () {
      var rect = launcher.getBoundingClientRect();
      var left = Math.max(DEFAULT_MARGIN, window.innerWidth - rect.width - DEFAULT_MARGIN);
      var top = Math.max(DEFAULT_MARGIN, window.innerHeight - rect.height - DEFAULT_MARGIN);
      setLauncherPosition(left, top, false);
    });
  }

  function setLauncherPosition(left, top, dragging) {
    if (!launcher) return;

    var width = launcher.offsetWidth || 150;
    var height = launcher.offsetHeight || 44;
    var maxLeft = Math.max(DEFAULT_MARGIN, window.innerWidth - width - DEFAULT_MARGIN);
    var maxTop = Math.max(DEFAULT_MARGIN, window.innerHeight - height - DEFAULT_MARGIN);
    var nextLeft = clamp(left, DEFAULT_MARGIN, maxLeft);
    var nextTop = clamp(top, DEFAULT_MARGIN, maxTop);

    launcher.style.left = nextLeft + 'px';
    launcher.style.top = nextTop + 'px';
    launcher.style.right = 'auto';
    launcher.style.bottom = 'auto';

    if (dragging) {
      launcher.setAttribute('aria-label', text('button') + ' — moving');
    } else {
      launcher.setAttribute('aria-label', text('button'));
    }
  }

  function saveLauncherPosition() {
    if (!launcher) return;

    var rect = launcher.getBoundingClientRect();
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
        left: Math.round(rect.left),
        top: Math.round(rect.top)
      }));
    } catch (error) {}
  }

  function bindAdminBarLink(callback) {
    var adminBarLink = document.querySelector('#wp-admin-bar-scbd-lite a');
    if (!adminBarLink) return;

    adminBarLink.setAttribute('href', '#scbd-lite-open');
    adminBarLink.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      if (typeof callback === 'function') {
        callback();
      } else {
        openModal();
      }
    });
  }

  function buildModal() {
    if (modal) return;

    modal = document.createElement('div');
    modal.id = 'scbd-lite-modal';
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

    if (!loaded) {
      loadValues();
    }
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
        renderFields(activeFields, data.values || values);
        setStatus(text('saved'));
      })
      .catch(function () {
        setStatus(text('error'));
      })
      .then(function () {
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

    return window.fetch(config.restUrl, options).then(function (response) {
      if (!response.ok) {
        throw new Error('SCBD Lite REST request failed with HTTP ' + response.status);
      }
      return response.json();
    });
  }

  function renderFields(fields, values) {
    var wrapper = modal.querySelector('.scbd-lite-modal-fields');
    if (!wrapper) return;

    activeFields = fields;
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

  function updateCounter(field, counter) {
    counter.textContent = 'Characters: ' + field.value.length;
  }

  function setStatus(message) {
    if (status) status.textContent = message || '';
  }

  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  window.addEventListener('resize', function () {
    if (!launcher) return;
    var rect = launcher.getBoundingClientRect();
    setLauncherPosition(rect.left, rect.top, false);
    saveLauncherPosition();
  });

  window.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin) {
      return;
    }
    if (event.data && event.data.type === 'scbd-lite-open') {
      openModal();
    }
  });

  ready(function () {
    if (isDiviBuilderParentShell()) {
      bindParentAdminBarBridge();
      return;
    }

    createLauncher();
  });
})();
