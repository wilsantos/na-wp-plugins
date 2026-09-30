(function () {
  'use strict';

  var REFRESH_INTERVAL_MS = 120000;

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function showToast(container, message, type) {
    if (!container) return;
    var el = document.createElement('div');
    el.className =
      'rounded-lg px-4 py-2 text-sm shadow-lg ' +
      (type === 'error'
        ? 'bg-destructive text-destructive-foreground'
        : 'bg-foreground text-background');
    el.textContent = message;
    container.appendChild(el);
    setTimeout(function () {
      el.remove();
    }, 3000);
  }

  async function writeToClipboard(text) {
    if (!navigator.clipboard || !navigator.clipboard.writeText) return false;
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (e) {
      return false;
    }
  }

  function isAbortError(err) {
    return err && err.name === 'AbortError';
  }

  async function shareMeeting(btn, toastContainer) {
    var name = btn.getAttribute('data-share-name') || '';
    var start = btn.getAttribute('data-share-start') || '';
    var end = btn.getAttribute('data-share-end') || '';
    var text = 'Reunião de NA: ' + name + '\nHorário: ' + start + ' - ' + end;
    var url = window.location.href;

    if (navigator.share) {
      try {
        await navigator.share({ title: name, text: text, url: url });
        return;
      } catch (err) {
        if (isAbortError(err)) return;
      }
    }

    if (await writeToClipboard(text + '\n' + url)) {
      showToast(toastContainer, 'Link da reunião copiado');
    } else {
      showToast(toastContainer, 'Não foi possível compartilhar nesta tela', 'error');
    }
  }

  async function copyText(btn, toastContainer) {
    var text = btn.getAttribute('data-copy-text') || '';
    if (!text) return;

    if (await writeToClipboard(text)) {
      showToast(toastContainer, 'Dados da reunião copiados');
      btn.classList.add('text-green-600');
      setTimeout(function () {
        btn.classList.remove('text-green-600');
      }, 2000);
    } else {
      showToast(toastContainer, 'Não foi possível copiar nesta tela', 'error');
    }
  }

  function bindSectionToggles(root) {
    qsa('.na-section-toggle', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var section = btn.closest('.na-section-collapsible');
        var content = qs('.na-section-collapsible__content', section);
        if (!section || !content) return;

        var isCollapsed = section.classList.contains('is-collapsed');
        if (isCollapsed) {
          section.classList.remove('is-collapsed');
          content.hidden = false;
          btn.setAttribute('aria-expanded', 'true');
        } else {
          section.classList.add('is-collapsed');
          content.hidden = true;
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    });
  }

  function bindActions(root, toastContainer) {
    qsa('.na-reunioes-share', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        shareMeeting(btn, toastContainer);
      });
    });

    qsa('.na-reunioes-copy', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        copyText(btn, toastContainer);
      });
    });
  }

  function filterQuery(embed) {
    var params = ['type=online', 'format=html'];
    var day = qs('.na-reunioes-filter-day', embed);
    var period = qs('.na-reunioes-filter-period', embed);
    if (day && day.value !== '') {
      params.push('day=' + encodeURIComponent(day.value));
    }
    if (period && period.value !== '') {
      params.push('period=' + encodeURIComponent(period.value));
    }
    return params.join('&');
  }

  function unwrapHtml(text) {
    var trimmed = String(text || '').replace(/^\uFEFF/, '').trim();
    if (trimmed.charAt(0) !== '"') return text;
    try {
      var parsed = JSON.parse(trimmed);
      if (typeof parsed === 'string') return parsed;
    } catch (e) {
      return text;
    }
    return text;
  }

  function bindToolbar(root, embed, toastContainer) {
    qsa('.na-reunioes-refresh', root).forEach(function (btn) {
      if (btn.getAttribute('data-bound') === '1') return;
      btn.setAttribute('data-bound', '1');
      btn.addEventListener('click', function () {
        refreshMeetings(embed, toastContainer);
      });
    });

    qsa('.na-reunioes-filters select', root).forEach(function (el) {
      if (el.getAttribute('data-bound') === '1') return;
      el.setAttribute('data-bound', '1');
      el.addEventListener('change', function () {
        refreshMeetings(embed, toastContainer);
      });
    });
  }

  function setFiltersDisabled(embed, disabled) {
    qsa('.na-reunioes-filters select', embed).forEach(function (el) {
      el.disabled = disabled;
    });
  }

  async function refreshMeetings(embed, toastContainer) {
    var restUrl = embed.getAttribute('data-rest-url');
    if (!restUrl) return;

    var requestId = (embed._naReqId || 0) + 1;
    embed._naReqId = requestId;

    var refreshBtn = qs('.na-reunioes-refresh', embed);
    var refreshIcon = qs('.na-reunioes-refresh-icon', embed);
    var main = qs('.na-reunioes-main', embed);
    var query = filterQuery(embed);

    if (refreshBtn) refreshBtn.disabled = true;
    if (refreshIcon) refreshIcon.classList.add('animate-spin');
    setFiltersDisabled(embed, true);
    if (main) main.classList.add('is-loading');

    try {
      var url = restUrl + (restUrl.indexOf('?') >= 0 ? '&' : '?') + query;
      var response = await fetch(url, {
        headers: {
          Accept: 'text/html',
          'X-WP-Nonce': (window.naReunioesEmbed && window.naReunioesEmbed.nonce) || '',
        },
        credentials: 'same-origin',
      });

      if (embed._naReqId !== requestId) return;

      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }

      var html = unwrapHtml(await response.text());
      if (embed._naReqId !== requestId) return;

      if (main) {
        main.innerHTML = html;
        bindActions(main, toastContainer);
        bindSectionToggles(main);
        bindToolbar(main, embed, toastContainer);
      }
    } catch (err) {
      if (embed._naReqId === requestId) {
        showToast(toastContainer, 'Erro ao atualizar reuniões', 'error');
      }
    } finally {
      if (embed._naReqId !== requestId) return;
      setFiltersDisabled(embed, false);
      if (main) main.classList.remove('is-loading');
      var activeBtn = qs('.na-reunioes-refresh', embed);
      var activeIcon = qs('.na-reunioes-refresh-icon', embed);
      if (activeBtn) activeBtn.disabled = false;
      if (activeIcon) activeIcon.classList.remove('animate-spin');
    }
  }

  function initEmbed(embed) {
    var toastContainer = qs('.na-reunioes-toast-container', embed);
    bindActions(embed, toastContainer);
    bindSectionToggles(embed);

    bindToolbar(embed, embed, toastContainer);

    setInterval(function () {
      refreshMeetings(embed, toastContainer);
    }, REFRESH_INTERVAL_MS);
  }

  function init() {
    qsa('#na-reunioes-embed').forEach(initEmbed);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
