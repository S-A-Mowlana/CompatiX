/**
 * CompatiX - Main Client-side Application Script
 * 
 * Modular Function Groups:
 * 1. Utility Functions & Toast Notifications
 * 2. Theme Toggle & Navigation
 * 3. Help Modals & Glossary Tooltips
 * 4. Autocomplete & Compatibility Checker Form
 * 5. Specter AI Chat (Memory, Focus, Upload, Suggestions, Profile Card, Inline Cards)
 * 6. Build Advisor Suggestion Generator
 * 7. Library Filtering & Active Chips
 * 8. Page Initialization & DOM Event Listeners
 */

// =============================================================================
// 1. UTILITY FUNCTIONS & TOAST NOTIFICATIONS
// =============================================================================

const glossaryTerms = {
  'VRAM': 'Video memory used by your graphics card to store images and textures.',
  'TDP': 'Thermal Design Power — roughly how much heat/power a component uses.',
  'DirectX version': 'The DirectX API level your PC supports for graphics and game compatibility.',
  'Integrated Graphics': 'A GPU built into your CPU, generally weaker than a dedicated graphics card.',
  'Dedicated GPU': 'A separate graphics card installed in your PC, generally more powerful.',
  'CPU cores/threads': 'CPU cores are processing units; threads are how tasks are split across them.',
  'OS version labels': 'The version of your operating system such as Windows 10 or Windows 11.'
};

function debounce(func, delay) {
  let timeoutId;
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(this, args), delay);
  };
}

function escapeHtml(unsafe) {
  return String(unsafe ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function showAlert(message, type = 'info') {
  showToast(message, type);
}

function showToast(message, type = 'info') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast-notification toast-${type}`;
  toast.setAttribute('role', 'status');
  toast.textContent = message;
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add('is-visible'));
  setTimeout(() => {
    toast.classList.remove('is-visible');
    setTimeout(() => toast.remove(), 250);
  }, 4000);
}

function setLoading(element, isLoading, label = 'Loading') {
  if (!element) return;
  element.classList.toggle('is-loading', isLoading);
  element.setAttribute('aria-busy', String(isLoading));
  if (isLoading) {
    element.insertAdjacentHTML('afterbegin', `<span class="inline-spinner" aria-label="${label}"></span>`);
  } else {
    element.querySelector('.inline-spinner')?.remove();
  }
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function renderPerformanceTier(tier) {
  if (!tier) return '';
  const slug = tier.toLowerCase().replace(/\s+/g, '-');
  const icon = tier === 'Lightweight' ? '◌' : tier === 'Demanding' ? '▲' : '◆';
  return `<span class="performance-tier-badge ${slug}">${icon} ${escapeHtml(tier)}</span>`;
}


// =============================================================================
// 2. THEME TOGGLE & NAVIGATION
// =============================================================================

function applyTheme(theme) {
  const chosen = theme === 'light' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', chosen);
  if (localStorage) {
    localStorage.setItem('compatixTheme', chosen);
  }
}

function initThemeToggle() {
  const nav = document.querySelector('nav .navbar-container');
  if (!nav) return;

  const savedTheme = localStorage.getItem('compatixTheme') || 'dark';
  const chosenTheme = savedTheme === 'light' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', chosenTheme);
  document.documentElement.setAttribute('data-glass', 'on');

  if (document.getElementById('theme-toggle')) return;

  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.id = 'theme-toggle';
  toggle.className = 'theme-toggle';
  toggle.setAttribute('aria-label', 'Toggle light/dark theme');
  toggle.setAttribute('aria-pressed', chosenTheme === 'light' ? 'true' : 'false');
  toggle.innerHTML = '<span class="theme-icon">🌙</span>';

  const renderThemeIcon = (theme) => {
    const iconSpan = toggle.querySelector('.theme-icon');
    if (iconSpan) iconSpan.textContent = theme === 'light' ? '☀️' : '🌙';
  };

  toggle.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme');
    const nextTheme = current === 'light' ? 'dark' : 'light';
    applyTheme(nextTheme);
    toggle.setAttribute('aria-pressed', nextTheme === 'light' ? 'true' : 'false');
    renderThemeIcon(nextTheme);
  });

  renderThemeIcon(chosenTheme);

  const specter = document.querySelector('.nav-ai-cta');
  if (specter && specter.parentElement) {
    const actions = document.createElement('div');
    actions.className = 'nav-actions';
    specter.parentElement.insertBefore(actions, specter);
    actions.append(toggle, specter);
  } else {
    const links = nav.querySelector('.navbar-links');
    if (links) links.append(toggle);
  }
}

function initNavigation() {
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.navbar-links');
  if (!links) return;

  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  links.querySelectorAll('a').forEach((link) => {
    const href = link.getAttribute('href');
    if (href === currentPage) link.classList.add('is-active');
  });

  if (!toggle) return;
  const closeMenu = () => {
    links.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open navigation menu');
  };

  toggle.addEventListener('click', () => {
    const isOpen = links.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
  });

  links.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
  });
}

function addBuildAdvisorNavLink() {
  const links = document.querySelector('.navbar-links');
  if (!links || links.querySelector('a[href="build-advisor.html"]')) return;
  const item = document.createElement('li');
  const link = document.createElement('a');
  link.href = 'build-advisor.html';
  link.textContent = 'Build Advisor';
  item.appendChild(link);
  links.appendChild(item);
}

function initPageLoader() {
  let loader = document.getElementById('compatix-page-loader');
  if (!loader) {
    loader = document.createElement('div');
    loader.id = 'compatix-page-loader';
    loader.className = 'compatix-loader-overlay compatix-skeleton-loader';
    loader.setAttribute('aria-hidden', 'true');
    loader.innerHTML = `
      <div class="page-skeleton-shell">
        <div class="page-skeleton-nav"><span class="page-skeleton-mark"></span><span class="page-skeleton-nav-line"></span><span class="page-skeleton-nav-links"></span><span class="page-skeleton-nav-links short"></span></div>
        <div class="page-skeleton-content"><span class="page-skeleton-eyebrow"></span><span class="page-skeleton-title"></span><span class="page-skeleton-copy"></span><span class="page-skeleton-copy short"></span><div class="page-skeleton-grid"><span></span><span></span><span></span></div></div>
      </div>`;
    document.body.prepend(loader);
  }

  const hideLoader = () => {
    if (loader) loader.classList.add('is-hidden');
  };

  const showLoader = () => {
    if (loader) loader.classList.remove('is-hidden');
  };

  window.hidePageLoader = hideLoader;
  window.showPageLoader = showLoader;

  // Reveal as soon as the document is ready; do not wait for images or API requests.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(hideLoader, 80), { once: true });
  } else {
    setTimeout(hideLoader, 80);
  }
}

function initHeroParallax() {
  const bg = document.querySelector('.hero-parallax-bg');
  if (!bg) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const orb1 = bg.querySelector('.orb-1');
  const orb2 = bg.querySelector('.orb-2');
  let ticking = false;

  const onScroll = () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const top = window.scrollY;
        if (top < 900) {
          if (orb1) orb1.style.transform = `translateY(${top * 0.12}px)`;
          if (orb2) orb2.style.transform = `translateY(${top * -0.08}px)`;
        }
        ticking = false;
      });
      ticking = true;
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
}

function initScrollAnimations() {
  const selectors = [
    'main section',
    '.section-heading-row',
    '.how-it-works .step',
    '.meet-specter',
    '.feature-grid .card',
    '.about-header',
    '.about-section',
    '.about-feature-card',
    '.library-layout',
    '.game-card',
    '.app-card',
    '.starter-card',
    '.checker-shell',
    '.build-advisor-grid',
    '.site-footer',
    '.requirements-card'
  ];

  const sections = document.querySelectorAll(selectors.join(', '));
  sections.forEach((section) => section.classList.add('reveal'));

  if (!('IntersectionObserver' in window)) {
    sections.forEach((section) => section.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

  sections.forEach((section) => observer.observe(section));
}

function initFloatingSpecter() {
  if (document.querySelector('.home-specter-float')) return;

  const anchor = document.createElement('a');
  anchor.className = 'home-specter-float';
  anchor.href = 'specter.html';
  anchor.title = 'Chat with Specter 👻';
  anchor.setAttribute('aria-label', 'Chat with Specter');
  anchor.innerHTML = '<img src="assets/specter-avatar-small.svg" alt="Specter">';
  document.body.appendChild(anchor);
}

function initHomeSpecterPrompts() {
  document.querySelectorAll('.home-specter-prompt').forEach((prompt) => {
    prompt.addEventListener('click', () => {
      const message = prompt.dataset.prompt?.trim();
      if (message) sessionStorage.setItem('specterPendingPrompt', message);
    });
  });
}


// =============================================================================
// 3. HELP MODALS & GLOSSARY TOOLTIPS
// =============================================================================

function closeAllHelpModals() {
  document.querySelectorAll('.modal').forEach((modal) => {
    modal.hidden = true;
    modal.style.display = 'none';
  });
  document.body.style.overflow = 'auto';
}

function openHelpModal(type) {
  const modal = type ? document.getElementById(`help-modal-${type}`) : null;
  if (!modal) return;

  closeAllHelpModals();
  modal.hidden = false;
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeHelpModal(type = null) {
  if (type) {
    const modal = document.getElementById(`help-modal-${type}`);
    if (modal) {
      modal.hidden = true;
      modal.style.display = 'none';
    }
  } else {
    closeAllHelpModals();
  }

  const anyOpenModal = Array.from(document.querySelectorAll('.modal')).some((modal) => !modal.hidden);
  if (!anyOpenModal) {
    document.body.style.overflow = 'auto';
  }
}

function bindHelpModalTriggers() {
  document.querySelectorAll('.help-icon').forEach((button) => {
    button.addEventListener('click', () => {
      openHelpModal(button.dataset.helpType);
    });
  });

  document.querySelectorAll('[data-close-help]').forEach((button) => {
    button.addEventListener('click', () => {
      closeHelpModal(button.dataset.closeHelp);
    });
  });

  document.querySelectorAll('.modal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeHelpModal();
      }
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeHelpModal();
    }
  });
}

function initGlossaryTooltips() {
  const termMap = glossaryTerms;
  const terms = Object.keys(termMap);
  const labels = Array.from(document.querySelectorAll('label, .spec-label, .req-item label, .details-label, .text-label, .form-group label'));

  labels.forEach((label) => {
    const text = label.textContent || '';
    const foundTerm = terms.find(term => text.toLowerCase().includes(term.toLowerCase()));
    if (!foundTerm || label.querySelector('.glossary-icon')) return;

    const icon = document.createElement('button');
    icon.type = 'button';
    icon.className = 'glossary-icon';
    icon.setAttribute('aria-label', 'Glossary definition');
    icon.innerHTML = '?';

    const tip = document.createElement('span');
    tip.className = 'glossary-tooltip';
    tip.textContent = termMap[foundTerm];

    icon.appendChild(tip);
    label.appendChild(icon);
  });
}


// =============================================================================
// 4. AUTOCOMPLETE & COMPATIBILITY CHECKER FORM
// =============================================================================

let availableGames = [];
let highlightedIndex = -1;
let selectedTitles = [];
let checkerCatalog = [];
let queryCatalogMap = {};

function showCheckerDataError(message) {
  const errorBox = document.getElementById('check-error');
  if (!errorBox) return;
  errorBox.hidden = false;
  errorBox.textContent = message;
  errorBox.style.display = 'block';
}

function clearCheckerDataError() {
  const errorBox = document.getElementById('check-error');
  if (!errorBox) return;
  errorBox.hidden = true;
  errorBox.textContent = '';
  errorBox.style.display = '';
}

function getInitials(name) {
  if (!name) return 'CX';
  const clean = String(name).replace(/[^a-zA-Z0-9\s]/g, '').trim();
  const words = clean.split(/\s+/).filter(Boolean);
  if (words.length === 0) return 'CX';
  if (words.length === 1) {
    return words[0].substring(0, 2).toUpperCase();
  }
  return (words[0][0] + words[1][0]).toUpperCase();
}

function initAutocomplete() {
  const gameInput = document.getElementById('game-input');
  const suggestionList = document.getElementById('game-suggestions');
  const gameWarning = document.getElementById('game-warning');

  if (!gameInput || !suggestionList) return;

  const catalogReady = window.CompatiXSearch?.loadCatalog()
    .then((catalog) => {
      checkerCatalog = catalog;
      catalog.forEach((item) => { queryCatalogMap[item.name.toLowerCase().trim()] = item; });
      clearCheckerDataError();
    })
    .catch(() => {
      checkerCatalog = [];
      showCheckerDataError('The game/app library could not be loaded. Please refresh and try again.');
    });

  let autocompleteDebounce = null;

  const renderSuggestions = (items) => {
    suggestionList.innerHTML = '';
    if (!items || items.length === 0) {
      const li = document.createElement('li');
      li.textContent = 'No matching titles found';
      li.className = 'no-match';
      suggestionList.appendChild(li);
      suggestionList.classList.add('active');
      return;
    }

    items.forEach((game) => {
      const li = document.createElement('li');
      li.textContent = game;
      li.dataset.value = game;
      li.addEventListener('click', () => selectGame(game));
      suggestionList.appendChild(li);
    });
    suggestionList.classList.add('active');
  };

  gameInput.addEventListener('input', function() {
    const value = this.value.trim().toLowerCase();
    highlightedIndex = -1;
    const selectedGame = document.getElementById('game-selected');
    if (selectedGame) selectedGame.value = '';

    if (!value) {
      suggestionList.classList.remove('active');
      if (gameWarning) gameWarning.style.display = 'none';
      return;
    }

    window.clearTimeout(autocompleteDebounce);
    autocompleteDebounce = window.setTimeout(async () => {
      await catalogReady;
      const matches = window.CompatiXSearch?.rank(value, checkerCatalog, 8) || [];
      matches.forEach((item) => { queryCatalogMap[item.name.toLowerCase().trim()] = item; });
      renderSuggestions(matches.map(item => item.name));
    }, 170);
  });

  gameInput.addEventListener('keydown', function(e) {
    const items = suggestionList.querySelectorAll('li:not(.no-match)');
    const itemCount = items.length;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!suggestionList.classList.contains('active')) return;
      highlightedIndex = Math.min(highlightedIndex + 1, itemCount - 1);
      updateHighlight(items);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (!suggestionList.classList.contains('active')) return;
      highlightedIndex = Math.max(highlightedIndex - 1, -1);
      updateHighlight(items);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (highlightedIndex >= 0 && items[highlightedIndex]) {
        selectGame(items[highlightedIndex].textContent);
      } else if (this.value.trim()) {
        selectGame(this.value.trim());
      }
    } else if (e.key === 'Tab') {
      if (highlightedIndex >= 0 && items[highlightedIndex]) {
        e.preventDefault();
        selectGame(items[highlightedIndex].textContent);
      }
    } else if (e.key === 'Escape') {
      suggestionList.classList.remove('active');
    }
  });

  document.addEventListener('click', function(e) {
    if (e.target !== gameInput && e.target !== suggestionList && !suggestionList.contains(e.target)) {
      suggestionList.classList.remove('active');
    }
  });

  document.querySelectorAll('[data-popular-title]').forEach(button => {
    button.addEventListener('click', () => selectGame(button.dataset.popularTitle || ''));
  });
}

function updateHighlight(items) {
  items.forEach((item, index) => {
    item.classList.remove('highlighted');
    if (index === highlightedIndex) {
      item.classList.add('highlighted');
      item.scrollIntoView({ block: 'nearest' });
    }
  });
}

function selectGame(gameName) {
  const gameInput = document.getElementById('game-input');
  const suggestionList = document.getElementById('game-suggestions');
  if (!gameInput) return;
  
  const cleanName = String(gameName || '').trim();
  if (!cleanName) return;
  const nameKey = cleanName.toLowerCase();

  const catalogMatch = checkerCatalog.find(item => String(item.name || '').toLowerCase().trim() === nameKey) || queryCatalogMap[nameKey];

  const coverUrl = catalogMatch?.cover || catalogMatch?.image_url || catalogMatch?.icon_url || catalogMatch?.background_image || '';
  const itemType = catalogMatch?.type || (catalogMatch?.category === 'App' || /app/i.test(catalogMatch?.category || '') ? 'app' : 'game');
  const categoryLabel = catalogMatch?.category || (itemType === 'app' ? 'App' : 'Game');

  selectedTitles = [{
    name: catalogMatch?.name || cleanName,
    id: catalogMatch?.id || cleanName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
    cover: coverUrl,
    type: itemType,
    category: categoryLabel
  }];

  renderSelectedTitles();
  gameInput.value = '';
  const selectedGame = document.getElementById('game-selected');
  if (selectedGame) selectedGame.value = selectedTitles[0]?.name || '';
  renderSelectedGamePreview();
  if (suggestionList) suggestionList.classList.remove('active');
  gameInput.focus();
}

function renderSelectedTitles() {
  const chips = document.getElementById('game-chips');
  if (!chips) return;
  // The preview card is the single visible representation of the selection.
  chips.innerHTML = '';
}

function clearSelectedGame() {
  selectedTitles = [];
  renderSelectedTitles();
  const selectedGame = document.getElementById('game-selected');
  if (selectedGame) selectedGame.value = '';
  renderSelectedGamePreview();
}

function renderSelectedGamePreview() {
  const preview = document.getElementById('selected-game-preview');
  const title = selectedTitles[0];
  if (!preview) return;
  if (!title) {
    preview.hidden = true;
    preview.innerHTML = '';
    return;
  }

  const initials = getInitials(title.name);
  const initialsHtml = `<span class="selected-game-initials" aria-label="${escapeHtml(title.name)} initials">${escapeHtml(initials)}</span>`;
  const typeLabel = title.type === 'app' ? 'App' : (title.category && title.category !== 'Game' ? title.category : 'Game');
  const hasValidCover = title.cover && typeof title.cover === 'string' && title.cover.trim() !== '' && !title.cover.includes('placeholder') && !title.cover.includes('compatix-logo');

  preview.hidden = false;
  preview.className = 'selected-game-preview selected-game-card';
  if (hasValidCover) {
    preview.innerHTML = `<img src="${escapeHtml(title.cover)}" alt="${escapeHtml(title.name)}"><span><strong>${escapeHtml(title.name)}</strong><small>${escapeHtml(typeLabel)}</small></span><button type="button" class="selected-game-remove" aria-label="Remove ${escapeHtml(title.name)}">&times;</button>`;
    const image = preview.querySelector('img');
    image?.addEventListener('error', () => image.replaceWith(document.createRange().createContextualFragment(initialsHtml).firstChild));
  } else {
    preview.innerHTML = `${initialsHtml}<span><strong>${escapeHtml(title.name)}</strong><small>${escapeHtml(typeLabel)}</small></span><button type="button" class="selected-game-remove" aria-label="Remove ${escapeHtml(title.name)}">&times;</button>`;
  }
  preview.querySelector('.selected-game-remove')?.addEventListener('click', clearSelectedGame);
}

function validateCheckForm() {
  const cpu = document.getElementById('cpu')?.value;
  const gpu = document.getElementById('gpu')?.value;
  const ram = document.getElementById('ram')?.value;
  const storage = document.getElementById('storage')?.value;
  const os = document.getElementById('os')?.value;
  const gameInput = document.getElementById('game-input')?.value;

  const errors = [];

  if (!cpu) errors.push('CPU is required');
  if (!gpu) errors.push('GPU is required');
  if (!ram) errors.push('RAM is required');
  if (!storage) errors.push('Storage is required');
  if (!os) errors.push('Operating System is required');
  if (!selectedTitles.length && !gameInput) errors.push('Please enter a Game or App to check');

  if (errors.length > 0) {
    showAlert(errors.join(', '), 'error');
    return false;
  }

  return true;
}

function setCheckerStep(step) {
  document.querySelectorAll('[data-checker-step]').forEach(panel => {
    const isTargetStep = Number(panel.dataset.checkerStep) === step;
    if (isTargetStep) {
      panel.hidden = false;
      panel.style.display = 'block';
      requestAnimationFrame(() => {
        panel.style.opacity = '1';
        panel.style.transform = 'translateY(0)';
      });
    } else {
      panel.hidden = true;
      panel.style.display = 'none';
      panel.style.opacity = '0';
      panel.style.transform = 'translateY(10px)';
    }
  });

  document.querySelectorAll('[data-checker-progress]').forEach(item => {
    const itemStep = Number(item.dataset.checkerProgress);
    const active = (step === 1 && itemStep === 1) || (step === 2 && itemStep === 2) || (step === 3 && itemStep === 3) || (step === 4 && itemStep === 3);
    const complete = (step === 2 && itemStep === 1) || (step >= 3 && itemStep <= 2) || (step === 4 && itemStep <= 3);
    item.classList.toggle('is-active', active);
    item.classList.toggle('is-complete', complete);
  });
  document.querySelector('.checker-page')?.classList.toggle('is-result-step', step >= 3);
}

function showAnalysisProgressScreen() {
  let container = document.getElementById('checker-analysis-container');
  const stepTarget = document.getElementById('checker-step-target');
  if (!container && stepTarget && stepTarget.parentElement) {
    container = document.createElement('div');
    container.id = 'checker-analysis-container';
    container.className = 'check-loading checker-analysis-container checker-step';
    container.setAttribute('data-checker-step', '3');
    container.innerHTML = `
      <div class="analysis-skeleton" aria-hidden="true">
        <span class="analysis-skeleton-heading"></span>
        <span class="analysis-skeleton-line"></span>
        <span class="analysis-skeleton-line short"></span>
        <div class="analysis-skeleton-grid"><span></span><span></span><span></span><span></span></div>
      </div>
      <div class="analysis-progress-track" aria-hidden="true"><div id="analysis-progress-bar" class="analysis-progress-bar"></div></div>
      <p class="check-loading-label" id="analysis-step-status">Comparing your PC with the selected title...</p>
    `;
    stepTarget.parentElement.insertBefore(container, stepTarget.nextSibling);
  }

  const statusLabel = document.getElementById('analysis-step-status');
  if (statusLabel) {
    statusLabel.className = 'check-loading-label';
    statusLabel.textContent = 'Comparing specs...';
  }
  setCheckerStep(3);
}

function animateAnalysisProgressBar(target = 100, duration = 0) {
  const bar = document.getElementById('analysis-progress-bar');
  if (bar) {
    bar.style.width = `${Math.max(0, Math.min(100, Number(target) || 0))}%`;
  }
  return duration > 0
    ? new Promise(resolve => window.setTimeout(resolve, duration))
    : Promise.resolve();
}

function hideAnalysisProgressScreen() {
  const container = document.getElementById('checker-analysis-container');
  if (container) {
    container.hidden = true;
    container.style.display = 'none';
  }
}

function validateCheckerPcStep() {
  const fields = [
    ['cpu', 'CPU'], ['gpu', 'GPU'], ['ram', 'RAM'],
    ['storage', 'Storage'], ['os', 'Operating System']
  ];
  const missing = fields.filter(([id]) => !document.getElementById(id)?.value).map(([, label]) => label);
  if (missing.length) {
    showAlert(`Please select your ${missing.join(', ')}.`, 'error');
    return false;
  }
  return true;
}

function renderCheckerResult(data) {
  const panel = document.getElementById('checker-step-result');
  const statusEl = document.getElementById('checker-result-status');
  const titleEl = document.getElementById('checker-result-title');
  const summaryEl = document.getElementById('checker-result-summary');
  const targetEl = document.getElementById('checker-result-target');
  const comparisonEl = document.getElementById('checker-comparison');
  const upgrades = document.getElementById('checker-upgrades');
  const upgradeList = document.getElementById('checker-upgrade-list');
  if (!panel || !statusEl || !comparisonEl) return;

  const mismatches = Array.isArray(data.mismatched_fields) ? data.mismatched_fields : [];
  const matched = Array.isArray(data.matched_fields) ? data.matched_fields : [];
  const status = data.compatible ? 'Compatible' : (matched.length >= 3 ? 'Partially Compatible' : 'Not Compatible');
  const statusClass = data.compatible ? 'is-compatible' : (status === 'Partially Compatible' ? 'is-partial' : 'is-not-compatible');
  const title = data.game_name || 'Selected title';
  titleEl.textContent = status;
  statusEl.textContent = status;
  statusEl.className = `checker-result-status ${statusClass}`;
  summaryEl.textContent = data.compatible
    ? 'Your system meets the minimum requirements for this title.'
    : mismatches.length ? `${mismatches.map(item => item.field).join(', ')} need attention before you can run it comfortably.` : 'Some requirements could not be matched with confidence.';
  const image = data.image_url || '';
  targetEl.innerHTML = `${image ? `<img src="${escapeHtml(image)}" alt="" onerror="this.remove();">` : ''}<div><strong>${escapeHtml(title)}</strong><span>Checked against your saved PC profile</span></div>`;

  const rows = [
    ['CPU', data.user_specs?.cpu, data.required_specs?.cpu, data.recommended_specs?.cpu],
    ['GPU', data.user_specs?.gpu, data.required_specs?.gpu, data.recommended_specs?.gpu],
    ['RAM', data.user_specs?.ram, data.required_specs?.ram, data.recommended_specs?.ram],
    ['Storage', data.user_specs?.storage, data.required_specs?.storage, data.recommended_specs?.storage],
    ['OS', data.user_specs?.os, data.required_specs?.os || data.recommended_specs?.os, data.recommended_specs?.os]
  ];
  comparisonEl.innerHTML = `<div class="checker-comparison-head"><span>Component</span><span>Your PC</span><span>Minimum</span><span>Recommended</span><span>Status</span></div>` + rows.map(([name, yours, minimum, recommended]) => {
    const failed = mismatches.some(item => item.field === name);
    const passed = matched.includes(name);
    const rowStatus = failed ? 'Below minimum' : (passed ? 'Meets minimum' : 'Review');
    return `<div class="checker-comparison-row ${failed ? 'is-failed' : passed ? 'is-passed' : ''}"><strong>${name}</strong><span>${escapeHtml(String(yours ?? 'Not provided'))}</span><span>${escapeHtml(String(minimum ?? 'Not specified'))}</span><span>${escapeHtml(String(recommended ?? minimum ?? 'Not specified'))}</span><b>${failed ? '!' : passed ? '✓' : '·'} <em>${rowStatus}</em></b></div>`;
  }).join('');

  if (mismatches.length) {
    upgrades.hidden = false;
    upgradeList.innerHTML = mismatches.map(item => `<li><strong>${escapeHtml(item.field)}</strong><span>${escapeHtml(item.suggested_upgrade || item.reason || 'Review this component against the minimum requirement.')}</span></li>`).join('');
  } else {
    upgrades.hidden = true;
    upgradeList.innerHTML = '';
  }
  setCheckerStep(3);
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// =============================================================================
// STEP 1: CHOICE, AUTO-DETECTION, REVIEW & MANUAL ENTRY
// =============================================================================

function showStep1View(viewName) {
  const choiceEl = document.getElementById('checker-pc-choice');
  const scanningEl = document.getElementById('checker-pc-scanning');
  const reviewEl = document.getElementById('checker-pc-review');
  const manualEl = document.getElementById('checker-pc-manual');

  [choiceEl, scanningEl, reviewEl, manualEl].forEach(el => {
    if (el) {
      el.hidden = true;
      el.style.display = 'none';
    }
  });

  if (viewName === 'choice' && choiceEl) {
    choiceEl.hidden = false;
    choiceEl.style.display = 'block';
  } else if (viewName === 'scanning' && scanningEl) {
    scanningEl.hidden = false;
    scanningEl.style.display = 'block';
  } else if (viewName === 'review' && reviewEl) {
    moveFormGroupsToSlots('review');
    reviewEl.hidden = false;
    reviewEl.style.display = 'block';
  } else if (viewName === 'manual' && manualEl) {
    moveFormGroupsToSlots('manual');
    manualEl.hidden = false;
    manualEl.style.display = 'block';
  }
}

function moveFormGroupsToSlots(mode) {
  const fields = ['cpu', 'gpu', 'ram', 'storage', 'os'];
  fields.forEach(field => {
    const group = document.getElementById(`group-${field}`);
    if (!group) return;
    if (mode === 'review') {
      const targetSlot = document.getElementById(`target-slot-${field}`);
      if (targetSlot) targetSlot.appendChild(group);
    } else if (mode === 'manual') {
      const manualSlot = document.getElementById(`manual-slot-${field}`);
      if (manualSlot) manualSlot.appendChild(group);
    }
  });
}

function runHardwareDetection() {
  showStep1View('scanning');

  // Short scan animation delay (~650ms) for clean UI feedback
  setTimeout(() => {
    const detectionResults = performBrowserHardwareScan();
    applyDetectedSpecs(detectionResults);
    showStep1View('review');
  }, 650);
}

function performBrowserHardwareScan() {
  const results = {
    cpu: { status: 'not-available', badgeText: 'Not available', lines: [], value: '' },
    gpu: { status: 'not-available', badgeText: 'Not available', lines: [], value: '' },
    ram: { status: 'not-available', badgeText: 'Not available', lines: [], value: '' },
    storage: { status: 'not-available', badgeText: 'Not available', lines: [], value: '' },
    os: { status: 'not-available', badgeText: 'Not available', lines: [], value: '' }
  };

  const ua = navigator.userAgent || '';
  const platform = navigator.platform || navigator.userAgentData?.platform || '';
  const isMobilePlatform = navigator.userAgentData?.mobile || /Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);
  const isTabletPlatform = /Tablet|iPad/i.test(ua) || (platform.includes('Mac') && navigator.maxTouchPoints > 1) || (ua.includes('Android') && !ua.includes('Mobile'));

  let deviceTypeStr = 'Desktop / Laptop';
  if (isTabletPlatform) {
    deviceTypeStr = 'Tablet';
  } else if (isMobilePlatform) {
    deviceTypeStr = 'Mobile Phone';
  }

  // 1. Operating System & Device Type Detection
  let detectedOsName = '';
  let selectedOsOption = '';
  let osBitStr = (ua.includes('x64') || ua.includes('Win64') || ua.includes('WOW64') || platform.includes('64')) ? '64-bit' : '32-bit';
  if (ua.includes('ARM') || platform.includes('ARM')) osBitStr = 'ARM';

  if (ua.includes('Win') || platform.includes('Win')) {
    if (ua.includes('Windows NT 10.0') || ua.includes('Windows 10') || ua.includes('Windows 11')) {
      detectedOsName = 'Windows';
      selectedOsOption = osBitStr === 'ARM' ? 'Windows 11 (ARM)' : (osBitStr === '32-bit' ? 'Windows 10 (32-bit)' : 'Windows 10 (64-bit)');
    } else if (ua.includes('Windows NT 6.1') || ua.includes('Windows 7')) {
      detectedOsName = 'Windows 7';
      selectedOsOption = 'Windows 7';
    } else if (ua.includes('Windows NT 6.2') || ua.includes('Windows NT 6.3') || ua.includes('Windows 8')) {
      detectedOsName = 'Windows 8 / 8.1';
      selectedOsOption = 'Windows 8 / 8.1';
    } else {
      detectedOsName = 'Windows';
      selectedOsOption = 'Windows 10 (64-bit)';
    }
    results.os.status = 'detected';
    results.os.badgeText = 'Detected';
    results.os.value = selectedOsOption;
    results.os.lines = [
      `✓ ${detectedOsName} (${osBitStr}) detected`,
      `✓ Device type: ${deviceTypeStr}`
    ];
  } else if (ua.includes('Macintosh') || (platform.includes('Mac') && !navigator.maxTouchPoints)) {
    results.os.status = 'detected';
    results.os.badgeText = 'Detected';
    results.os.value = 'macOS (Latest)';
    results.os.lines = [
      `✓ macOS operating system detected`,
      `✓ Device type: ${deviceTypeStr}`
    ];
  } else if (ua.includes('iPhone') || ua.includes('iPad') || (platform.includes('Mac') && navigator.maxTouchPoints > 1)) {
    results.os.status = 'detected';
    results.os.badgeText = 'Detected';
    results.os.value = 'other';
    results.os.lines = [
      `✓ iOS / iPadOS detected`,
      `✓ Device type: ${deviceTypeStr}`
    ];
  } else if (ua.includes('Android')) {
    results.os.status = 'detected';
    results.os.badgeText = 'Detected';
    results.os.value = 'other';
    results.os.lines = [
      `✓ Android platform detected`,
      `✓ Device type: ${deviceTypeStr}`
    ];
  } else if (ua.includes('Linux') || platform.includes('Linux')) {
    results.os.status = 'detected';
    results.os.badgeText = 'Detected';
    let linuxDistro = 'Other Linux Distribution';
    if (ua.includes('Ubuntu')) linuxDistro = 'Ubuntu';
    else if (ua.includes('Fedora')) linuxDistro = 'Fedora';
    else if (ua.includes('Debian')) linuxDistro = 'Debian';
    else if (ua.includes('Mint')) linuxDistro = 'Linux Mint';
    else if (ua.includes('Arch')) linuxDistro = 'Arch Linux';
    results.os.value = linuxDistro;
    results.os.lines = [
      `✓ Linux distribution detected (${linuxDistro})`,
      `✓ Device type: ${deviceTypeStr}`
    ];
  } else {
    results.os.status = 'not-available';
    results.os.badgeText = 'Not available';
    results.os.value = '';
    results.os.lines = [
      `⚠ Operating system could not be detected automatically`,
      `⚠ Device type: ${deviceTypeStr}`
    ];
  }

  // 2. CPU Thread & Model Detection
  const cores = navigator.hardwareConcurrency;
  if (cores && typeof cores === 'number') {
    results.cpu.status = 'approximate';
    results.cpu.badgeText = 'Approximate';
    results.cpu.value = ''; // DO NOT GUESS CPU MODEL!
    results.cpu.lines = [
      `✓ ${cores} logical CPU cores detected`,
      `⚠ CPU model cannot be detected automatically`
    ];
  } else {
    results.cpu.status = 'not-available';
    results.cpu.badgeText = 'Not available';
    results.cpu.value = '';
    results.cpu.lines = [
      `⚠ Logical CPU core count unavailable in browser`,
      `⚠ CPU model cannot be detected automatically`
    ];
  }

  // 3. RAM Detection
  const devMem = navigator.deviceMemory;
  if (devMem && typeof devMem === 'number') {
    results.ram.status = 'approximate';
    results.ram.badgeText = 'Approximate';
    let ramVal = '8';
    if (devMem <= 2) ramVal = '2';
    else if (devMem <= 4) ramVal = '4';
    else if (devMem <= 6) ramVal = '6';
    else ramVal = '8';

    results.ram.value = ramVal;
    results.ram.lines = [
      `✓ Approximately ${devMem} GB (browser-reported RAM)`,
      `⚠ Browser Memory API is approximate (capped up to 8 GB for privacy)`
    ];
  } else {
    results.ram.status = 'not-available';
    results.ram.badgeText = 'Not available';
    results.ram.value = '';
    results.ram.lines = [
      `⚠ Memory API (deviceMemory) not supported by browser`,
      `⚠ Please select your RAM capacity manually`
    ];
  }

  // 4. GPU Detection via WebGL
  try {
    const canvas = document.createElement('canvas');
    const gl = canvas.getContext('webgl2') || canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
    if (gl) {
      const ext = gl.getExtension('WEBGL_debug_renderer_info');
      if (ext) {
        const vendor = gl.getParameter(ext.UNMASKED_VENDOR_WEBGL) || '';
        const renderer = gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) || '';
        const cleanRenderer = parseCleanGpuString(renderer || vendor);
        const lowerRenderer = cleanRenderer.toLowerCase();

        const isSoftwareOrMasked = !cleanRenderer ||
          lowerRenderer.includes('swiftshader') ||
          lowerRenderer.includes('basic render') ||
          lowerRenderer.includes('software') ||
          lowerRenderer.includes('llvmpipe') ||
          lowerRenderer.includes('gallium');

        if (!isSoftwareOrMasked) {
          const matchedGpu = findExactOrHighConfidenceGpuOption(cleanRenderer);
          if (matchedGpu) {
            results.gpu.status = 'detected';
            results.gpu.badgeText = 'Detected';
            results.gpu.value = matchedGpu;
            results.gpu.lines = [
              `✓ WebGL GPU: ${cleanRenderer}`,
              `✓ Matched GPU model: ${matchedGpu}`
            ];
          } else {
            results.gpu.status = 'approximate';
            results.gpu.badgeText = 'Approximate';
            results.gpu.value = '';
            results.gpu.lines = [
              `⚠ WebGL exposed GPU string: "${cleanRenderer}"`,
              `⚠ GPU model could not be reliably detected`
            ];
          }
        } else {
          results.gpu.status = 'not-available';
          results.gpu.badgeText = 'Not available';
          results.gpu.value = '';
          results.gpu.lines = [
            `⚠ GPU model could not be detected automatically`,
            `⚠ Browser exposed generic/software WebGL renderer: ${cleanRenderer || 'Masked'}`
          ];
        }
      } else {
        results.gpu.status = 'not-available';
        results.gpu.badgeText = 'Not available';
        results.gpu.value = '';
        results.gpu.lines = [
          `⚠ GPU model could not be detected automatically`,
          `⚠ WebGL hardware debug extension disabled by browser`
        ];
      }
    } else {
      results.gpu.status = 'not-available';
      results.gpu.badgeText = 'Not available';
      results.gpu.value = '';
      results.gpu.lines = [
        `⚠ WebGL context unavailable in current browser`,
        `⚠ GPU model could not be detected automatically`
      ];
    }
  } catch (e) {
    results.gpu.status = 'not-available';
    results.gpu.badgeText = 'Not available';
    results.gpu.value = '';
    results.gpu.lines = [
      `⚠ GPU model could not be detected automatically`,
      `⚠ Exception reading WebGL renderer parameters`
    ];
  }

  // 5. Storage (Privacy Restricted)
  results.storage.status = 'not-available';
  results.storage.badgeText = 'Not available';
  results.storage.value = '';
  results.storage.lines = [
    `⚠ Storage space cannot be scanned automatically (Browser privacy sandbox)`,
    `⚠ Please select your available free storage space below`
  ];

  return results;
}

function parseCleanGpuString(rawStr) {
  if (!rawStr) return '';
  let str = rawStr;
  const angleMatch = rawStr.match(/ANGLE\s*\([^,]+,\s*([^,]+)/i);
  if (angleMatch && angleMatch[1]) str = angleMatch[1];
  return str.replace(/Direct3D\d+.*$/i, '')
            .replace(/vs_\d+_\d+.*$/i, '')
            .replace(/OpenGL.*$/i, '')
            .replace(/\(R\)/gi, '')
            .replace(/\(TM\)/gi, '')
            .trim() || rawStr;
}

function findExactOrHighConfidenceGpuOption(cleanStr) {
  const select = document.getElementById('gpu');
  if (!select) return '';
  const search = cleanStr.toLowerCase();
  
  // Try exact model number match first (e.g. "rtx 3070" or "rx 6700")
  for (const opt of select.options) {
    if (!opt.value || opt.value === 'other') continue;
    const val = opt.value.toLowerCase();
    
    // Extract specific model tokens like 3070, 4080, 1660, 6700
    const modelNumMatch = val.match(/(?:rtx|gtx|rx)\s*(\d{4}|\d{3})/i);
    if (modelNumMatch && modelNumMatch[1]) {
      const num = modelNumMatch[1];
      if (search.includes(num) && (search.includes('rtx') === val.includes('rtx') || search.includes('geforce') === val.includes('geforce') || search.includes('radeon') === val.includes('radeon'))) {
        return opt.value;
      }
    } else if (val.includes('intel uhd') && search.includes('intel') && search.includes('uhd')) {
      return opt.value;
    } else if (val.includes('intel iris') && search.includes('intel') && search.includes('iris')) {
      return opt.value;
    }
  }

  // Secondary strict substring check
  for (const opt of select.options) {
    if (!opt.value || opt.value === 'other') continue;
    const val = opt.value.toLowerCase();
    if (search.length >= 10 && search === val) return opt.value;
  }
  
  return '';
}

function applyDetectedSpecs(results) {
  const fields = ['cpu', 'gpu', 'ram', 'storage', 'os'];
  const saved = JSON.parse(localStorage.getItem('compatixSpecs') || '{}');

  fields.forEach(field => {
    const res = results[field];
    if (!res) return;

    const pill = document.getElementById(`pill-review-${field}`);
    const list = document.getElementById(`list-review-${field}`);
    const select = document.getElementById(field);

    if (pill) {
      pill.textContent = res.badgeText;
      pill.className = `status-badge-pill ${res.status === 'detected' ? 'pill-detected' : res.status === 'approximate' ? 'pill-approx' : 'pill-manual'}`;
    }

    if (list) {
      list.innerHTML = (res.lines || []).map(line => {
        const isPass = line.startsWith('✓');
        const isApprox = line.includes('approximate') || line.includes('Approximate') || line.includes('reported');
        const cls = isPass ? 'confidence-pass' : (isApprox ? 'confidence-approx' : 'confidence-warn');
        return `<li class="${cls}">${escapeHtml(line)}</li>`;
      }).join('');
    }

    if (select) {
      if (res.value) {
        select.value = res.value;
      } else if (saved[field]) {
        select.value = saved[field];
      } else {
        select.value = '';
      }
    }
  });
}

function initCheckerWizard() {
  const form = document.getElementById('check-form');
  if (!form) return;

  // Pre-load saved specs if available
  try {
    const saved = JSON.parse(localStorage.getItem('compatixSpecs') || '{}');
    ['cpu', 'gpu', 'ram', 'storage', 'os'].forEach(field => {
      const select = document.getElementById(field);
      if (select && saved[field]) select.value = saved[field];
    });
  } catch (_) {}

  // Choice Screen buttons
  document.getElementById('btn-choice-auto')?.addEventListener('click', () => {
    runHardwareDetection();
  });
  document.getElementById('btn-choice-manual')?.addEventListener('click', () => {
    showStep1View('manual');
  });

  // Back to Choice buttons
  document.getElementById('btn-review-back')?.addEventListener('click', () => showStep1View('choice'));
  document.getElementById('btn-manual-back')?.addEventListener('click', () => showStep1View('choice'));

  // Edit Specifications button
  document.getElementById('btn-edit-specs')?.addEventListener('click', () => {
    const slotsGrid = document.getElementById('detected-review-slots');
    slotsGrid?.classList.add('is-editing');
    
    // Focus on the first empty or unselected spec field
    const emptyField = ['cpu', 'gpu', 'ram', 'storage', 'os'].find(id => !document.getElementById(id)?.value);
    const targetId = emptyField || 'cpu';
    const targetEl = document.getElementById(targetId);
    if (targetEl) {
      targetEl.focus();
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    showToast('Please verify or select your specifications in the highlighted fields.', 'info');
  });

  // Continue buttons from Review or Manual mode to Step 2
  const continueToTarget = () => {
    if (validateCheckerPcStep()) setCheckerStep(2);
  };
  document.getElementById('btn-review-continue')?.addEventListener('click', continueToTarget);
  document.getElementById('btn-manual-continue')?.addEventListener('click', continueToTarget);

  // Step 2 & 3 navigation
  document.getElementById('back-to-pc')?.addEventListener('click', () => setCheckerStep(1));
  document.getElementById('check-another-game')?.addEventListener('click', () => {
    selectedTitles = [];
    renderSelectedTitles();
    const input = document.getElementById('game-input');
    if (input) { input.value = ''; input.focus(); }
    setCheckerStep(2);
  });

  // Default to choice screen on load
  showStep1View('choice');
  setCheckerStep(1);
}

async function submitCheckForm(e) {
  e.preventDefault();

  if (!validateCheckForm()) {
    return;
  }

  const formData = new FormData(document.getElementById('check-form'));
  const submitButton = document.querySelector('#check-form button[type="submit"]');
  setLoading(submitButton, true, 'Analyzing specifications…');
  showAnalysisProgressScreen();

  const data = {
    cpu: formData.get('cpu'),
    gpu: formData.get('gpu'),
    ram: formData.get('ram'),
    storage: formData.get('storage'),
    os: formData.get('os'),
    selected_game: selectedTitles[0]?.name || document.getElementById('game-input').value.trim(),
  };
  const titles = selectedTitles.length ? selectedTitles : [{ name: data.selected_game, id: data.selected_game.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') }];
  if (titles.length > 1) {
    data.title_ids = titles.map(title => title.id);
    data.title_names = Object.fromEntries(titles.map(title => [title.id, title.name]));
  } else {
    data.title_id = titles[0].id;
  }

  const startTime = Date.now();

  try {
    const result = await runStaticCompatibilityCheck(data, titles);
    localStorage.setItem('compatixSpecs', JSON.stringify({ cpu: data.cpu, gpu: data.gpu, ram: data.ram, storage: data.storage, os: data.os }));

    const elapsed = Date.now() - startTime;
    const minDelay = 650;
    const remainingDelay = Math.max(0, minDelay - elapsed);

    await animateAnalysisProgressBar(100, remainingDelay);
    hideAnalysisProgressScreen();

    if (Array.isArray(result)) {
      sessionStorage.setItem('compatibilityResults', JSON.stringify(result));
      if (window.showPageLoader) window.showPageLoader();
      window.location.href = 'results.html';
    } else {
      sessionStorage.setItem('compatibilityResult', JSON.stringify(result));
      renderCheckerResult(result);
      if (window.showPageLoader) window.showPageLoader();
      window.location.href = 'results.html';
    }
  } catch (error) {
    hideAnalysisProgressScreen();
    const debugMessage = error instanceof Error ? error.message : String(error);
    const errorBox = document.getElementById('check-error');
    if (errorBox) {
      errorBox.hidden = false;
      errorBox.textContent = debugMessage;
      errorBox.style.display = 'block';
    }
    showAlert(`Compatibility check failed: ${debugMessage}`, 'error');
  } finally {
    setLoading(submitButton, false);
  }
}

function copyCompatibilityResult(data) {
  const status = data.compatible ? 'Compatible' : 'Not compatible';
  const summary = `${data.game_name || 'Compatibility Check'} - ${status}`;
  navigator.clipboard?.writeText(summary).then(() => showToast('Results copied to clipboard!', 'success')).catch(() => showToast('Could not copy results.', 'error'));
}

async function runStaticCompatibilityCheck(data, titles) {
  const [gamesResponse, appsResponse, titlesResponse] = await Promise.all([
    fetch('games_cache.json'), fetch('apps_cache.json'), fetch('titles.json')
  ]);
  const gamesPayload = await gamesResponse.json();
  const appsPayload = await appsResponse.json();
  const titlesPayload = await titlesResponse.json();
  const catalog = [
    ...(Array.isArray(gamesPayload) ? gamesPayload : (gamesPayload.data || [])),
    ...(Array.isArray(appsPayload) ? appsPayload : (appsPayload.data || [])),
    ...(titlesPayload.entries || [])
  ];
  const requested = titles?.[0]?.name || data.selected_game;
  const normalized = String(requested || '').trim().toLowerCase();
  const item = catalog.find(entry => String(entry.name || '').trim().toLowerCase() === normalized)
    || catalog.find(entry => String(entry.name || '').trim().toLowerCase().includes(normalized));
  if (!item) throw new Error('No match was found for that game or app.');
  const minimum = item.minimum || { cpu: item.min_cpu, gpu: item.min_gpu, ram: item.min_ram, storage: item.min_storage, os: item.supported_os };
  const recommended = item.recommended || { cpu: item.rec_cpu || minimum.cpu, gpu: item.rec_gpu || minimum.gpu, ram: item.rec_ram || minimum.ram, storage: item.rec_storage || minimum.storage, os: minimum.os };
  const user = { cpu: data.cpu, gpu: data.gpu, ram: Number(data.ram), storage: Number(data.storage), os: data.os };
  const mismatched_fields = [];
  const matched_fields = [];
  [['RAM', user.ram, minimum.ram], ['Storage', user.storage, minimum.storage]].forEach(([field, actual, required]) => {
    if (!required || actual >= Number(required)) matched_fields.push(field);
    else mismatched_fields.push({ field, reason: `At least ${required} GB is recommended.` });
  });
  ['CPU', 'GPU', 'OS'].forEach(field => matched_fields.push(field));
  return { game_name: item.name, image_url: item.image_url || item.background_image || item.cover || '', compatible: mismatched_fields.length === 0, matched_fields, mismatched_fields, user_specs: user, required_specs: minimum, recommended_specs: recommended, source: item.source || 'CompatiX static catalog' };
}


// =============================================================================
// 5. SPECTER AI CHAT (Memory, Focus, Upload, Suggestions, Profile Card, Inline Cards)
// =============================================================================

let chatHistory = [];
let savedSpecs = { cpu: '', gpu: '', ram: '', storage: '', os: '' };
let specterMessageCounter = 0;
let isSending = false;

function buildCompatixContext() {
  const path = window.location.pathname.toLowerCase();
  const page = path.split('/').pop() || 'index.html';
  const context = { page, data: null };
  const params = new URLSearchParams(window.location.search);
  if (page === 'game-details.html' || page === 'app-details.html') {
    context.data = {
      title_id: params.get('id') || '',
      title_name: document.querySelector('#game-name, #app-name')?.textContent.trim() || '',
      requirements: ['#min-cpu', '#min-gpu', '#min-ram', '#min-storage', '#min-os', '#rec-cpu', '#rec-gpu', '#rec-ram', '#rec-storage', '#rec-os']
        .reduce((out, selector) => { const el = document.querySelector(selector); if (el?.textContent.trim()) out[selector.slice(1)] = el.textContent.trim(); return out; }, {})
    };
    fetch('titles.json').then(response => response.ok ? response.json() : null).then(catalog => {
      const entries = Array.isArray(catalog) ? catalog : (catalog?.titles || []);
      const match = entries.find(entry => String(entry.id ?? entry.title_id ?? '') === String(context.data.title_id) || String(entry.name ?? entry.title_name ?? '').toLowerCase() === context.data.title_name.toLowerCase());
      if (match) { context.data.requirements = match.requirements || { minimum: match.minimum, recommended: match.recommended }; window.COMPATIX_CONTEXT = context; }
    }).catch(() => {});
  } else if (page === 'results.html') {
    let result = null;
    try { result = JSON.parse(sessionStorage.getItem('compatibilityResult') || 'null'); } catch (_) {}
    context.data = { title_name: result?.game_name || '', user_specs: result?.user_specs || {} };
  } else if (page === 'games-library.html' || page === 'apps-library.html') {
    context.data = { filters: Object.fromEntries(params.entries()) };
  } else if (page === 'build-advisor.html') {
    context.data = { budget: document.querySelector('#budget, [name="budget"]')?.value || params.get('budget') || '', mode: document.querySelector('#mode, [name="mode"]')?.value || params.get('mode') || '' };
  }
  if (page === 'specter.html') {
    try {
      const pendingContext = JSON.parse(sessionStorage.getItem('specterPendingContext') || 'null');
      if (pendingContext) return window.COMPATIX_CONTEXT = pendingContext;
    } catch (_) {}
  }
  window.COMPATIX_CONTEXT = (page === 'index.html' || page === 'check.html' || page === 'checker.html') ? null : context;
}

function openSpecterWithContext(message) {
  let prompt = message;
  let context = window.COMPATIX_CONTEXT || null;
  const currentPage = (window.location.pathname.split('/').pop() || '').toLowerCase();

  if (currentPage === 'results.html' || currentPage === 'results.php') {
    let result = window.currentCompatibilityResult || null;
    if (!result) {
      try { result = JSON.parse(sessionStorage.getItem('compatibilityResult') || 'null'); } catch (_) {}
    }
    if (result && typeof result === 'object') {
      const userSpecs = result.user_specs || {};
      const failures = Array.isArray(result.mismatched_fields) ? result.mismatched_fields : [];
      const compatible = result.compatible === true;
      const verdict = compatible ? 'Compatible' : result.compatible === false ? 'Not compatible' : 'Partially compatible';
      const reasons = failures.map(item => {
        const field = String(item?.field || 'component');
        const reason = String(item?.reason || `my ${field} is below the minimum requirement`);
        return `${field}: ${reason}`;
      });
      const reasonText = reasons.length ? reasons.join('; ') : (compatible ? 'all of my listed requirements are met' : 'one or more requirements need review');
      const value = key => String(userSpecs[key] ?? 'Not specified');
      prompt = `I checked ${String(result.game_name || 'this title')} and it says ${verdict} because ${reasonText}. My specs: CPU ${value('cpu')}, GPU ${value('gpu')}, RAM ${value('ram')}, Storage ${value('storage')}, OS ${value('os')}. Can you explain why and what my options are?`;
      context = {
        page: 'results.html',
        data: {
          title_name: result.game_name || '',
          user_specs: userSpecs,
          verdict,
          compatible,
          failed_fields: failures.map(item => String(item?.field || '')),
          reasons,
          required_specs: result.required_specs || {},
          recommended_specs: result.recommended_specs || {}
        }
      };
    }
  }

  sessionStorage.setItem('specterPendingPrompt', prompt);
  sessionStorage.setItem('specterPendingContext', JSON.stringify(context));
  window.location.href = 'specter.html';
}

function initContextActions() {
  document.querySelectorAll('[data-specter-context]').forEach((button) => {
    button.addEventListener('click', () => openSpecterWithContext(button.dataset.specterContext || button.textContent.trim()));
  });
}

function initAssistantFeatures() {
  const editButton = document.getElementById('edit-specs-button');
  const closeButton = document.getElementById('close-specs-button');
  const specsModal = document.getElementById('specs-modal');
  const specsForm = document.getElementById('specs-form');
  const uploadButton = document.getElementById('upload-specs-button');
  const fileInput = document.getElementById('specs-file-input');

  if (!editButton || !specsModal || !specsForm) return;

  editButton.addEventListener('click', () => {
    Object.entries(savedSpecs).forEach(([key, value]) => {
      const input = document.getElementById(`profile-${key}`);
      if (input) input.value = value;
    });
    specsModal.hidden = false;
  });

  closeButton?.addEventListener('click', () => { specsModal.hidden = true; });
  
  specsForm.addEventListener('submit', (event) => {
    event.preventDefault();
    ['cpu', 'gpu', 'ram', 'storage', 'os'].forEach((key) => {
      savedSpecs[key] = document.getElementById(`profile-${key}`).value.trim();
    });
    updateProfileCard();
    specsModal.hidden = true;
    showToast('PC specifications updated!', 'success');
  });

  uploadButton?.addEventListener('click', () => fileInput?.click());
  fileInput?.addEventListener('change', () => {
    const file = fileInput.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => parseUploadedSpecs(String(reader.result || ''));
    reader.readAsText(file);
    fileInput.value = '';
  });
  
  updateProfileCard();
}

function updateProfileCard() {
  const values = document.getElementById('pc-profile-values');
  if (!values) return;
  values.innerHTML = ['cpu', 'gpu', 'ram', 'storage', 'os']
    .map((key) => `
      <div class="pc-spec-row">
        <span class="pc-spec-key">${key.toUpperCase()}</span>
        <span class="pc-spec-val">${escapeHtml(savedSpecs[key] || 'Not set')}</span>
      </div>
    `)
    .join('');
}

async function parseUploadedSpecs(text) {
  const formData = new FormData();
  formData.append('text', text);
  try {
    const response = await fetch('parse_specs.php', { method: 'POST', body: formData });
    const data = await response.json();
    if (!response.ok || !data.found) throw new Error('No specs found');
    ['cpu', 'gpu', 'ram', 'os'].forEach((key) => {
      if (data[key]) savedSpecs[key] = data[key];
    });
    updateProfileCard();
    displayMessage(`I've detected your specs from the file: ${savedSpecs.cpu || 'CPU not found'}, ${savedSpecs.gpu || 'GPU not found'}, ${savedSpecs.ram || 'RAM not found'}. Let me know if anything looks wrong!`, 'ai');
  } catch (error) {
    displayMessage("I couldn't read specs from that file. You can enter them manually instead.", 'ai');
  }
}

function initChat() {
  const chatForm = document.getElementById('chat-form');
  if (!chatForm) return;

  const messageInput = document.getElementById('message-input');
  const sendButton = document.getElementById('send-button');

  // Mobile sidebar drawer & backdrop controls
  const sidebarToggle = document.getElementById('specter-sidebar-toggle');
  const sidebarClose = document.getElementById('specter-sidebar-close');
  const sidebar = document.getElementById('specter-sidebar');
  const backdrop = document.getElementById('specter-sidebar-backdrop');

  const closeSidebar = () => {
    sidebar?.classList.remove('is-open');
    backdrop?.classList.remove('is-open');
    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
  };

  sidebarToggle?.addEventListener('click', () => {
    const isOpen = sidebar?.classList.toggle('is-open');
    backdrop?.classList.toggle('is-open', Boolean(isOpen));
    sidebarToggle.setAttribute('aria-expanded', String(Boolean(isOpen)));
  });

  sidebarClose?.addEventListener('click', closeSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  // Quick Action Buttons
  document.querySelectorAll('.quick-action-btn[data-action]').forEach((btn) => {
    btn.addEventListener('click', () => {
      closeSidebar();
      const action = btn.dataset.action;
      const fileInput = document.getElementById('specs-file-input');

      if (action === 'check-game') {
        if (messageInput) messageInput.value = 'Will Cyberpunk 2077 run on my PC?';
        sendMessage();
      } else if (action === 'upload-specs') {
        fileInput?.click();
      } else if (action === 'ask-upgrades') {
        if (messageInput) messageInput.value = 'What should I upgrade for better performance?';
        sendMessage();
      }
    });
  });

  // Conversation Starter Cards
  document.querySelectorAll('.starter-card').forEach((card) => {
    card.addEventListener('click', () => {
      const promptText = card.dataset.prompt || card.querySelector('.starter-card-text')?.textContent || card.textContent.trim();
      if (messageInput && promptText) {
        messageInput.value = promptText;
      }
      sendMessage();
    });
  });

  if (sendButton) {
    sendButton.onclick = (event) => {
      event.preventDefault();
      sendMessage();
    };
  }
  if (messageInput) {
    messageInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        sendMessage();
      }
    });
  }

  chatForm.addEventListener('submit', (event) => {
    event.preventDefault();
    sendMessage();
  });

  const messagesContainer = document.querySelector('.messages');
  if (messagesContainer && messagesContainer.children.length === 0) {
    const greeting = 'Hey! Ask me anything about your PC\'s compatibility, specs, or upgrades.';
    displayMessage(greeting, 'ai');
    chatHistory.push({ role: 'assistant', content: greeting });
  }

  const pendingPrompt = sessionStorage.getItem('specterPendingPrompt');
  if (pendingPrompt) {
    sessionStorage.removeItem('specterPendingPrompt');
    sessionStorage.removeItem('specterPendingContext');
    messageInput.value = pendingPrompt;
    window.setTimeout(() => sendMessage(), 0);
  }
}

async function sendMessage() {
  if (isSending) return;

  const messageInput = document.getElementById('message-input');
  const sendButton = document.getElementById('send-button');
  if (!messageInput) return;

  const userMessage = messageInput.value.trim();
  if (!userMessage) return;

  // Hide starter cards when the first user message is sent
  const starterContainer = document.getElementById('starter-cards-container');
  if (starterContainer) {
    starterContainer.classList.add('hidden');
  }

  isSending = true;
  if (sendButton) sendButton.disabled = true;
  messageInput.disabled = true;

  displayMessage(userMessage, 'user');
  chatHistory.push({ role: 'user', content: userMessage });
  messageInput.value = '';

  showTypingIndicator();

  try {
    const groqKey = window.COMPATIX_GROQ_API_KEY || '';
    if (!groqKey) throw new Error('Specter is not configured for this static deployment.');
    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${groqKey}`,
      },
      body: JSON.stringify({ model: 'openai/gpt-oss-20b', messages: [{ role: 'system', content: 'You are Specter, a friendly PC compatibility assistant. Reply in concise plain text.' }, ...chatHistory], temperature: 0.7, max_tokens: 500 }),
    });

    if (!response.ok) {
      throw new Error(`Specter returned HTTP ${response.status}`);
    }

    const groqData = await response.json();
    const data = { reply: groqData.choices?.[0]?.message?.content || '' };
    if (!data.reply) throw new Error('Specter response did not include a reply.');
    removeTypingIndicator();
    displayMessage(data.reply, 'ai');
    chatHistory.push({ role: 'assistant', content: data.reply });
    if (Array.isArray(data.suggestions)) displaySuggestions(data.suggestions);
    if (data.compatibility_result) displayCompatibilityResult(data.compatibility_result);
    if (data.mentioned_item) displayMentionedItem(data.mentioned_item);
  } catch (error) {
    removeTypingIndicator();
    displayMessage('Something went wrong. Please try again.', 'ai');
  } finally {
    removeTypingIndicator();
    isSending = false;
    if (sendButton) sendButton.disabled = false;
    messageInput.disabled = false;
    messageInput.focus();
  }
}

function displaySuggestions(suggestions) {
  const messagesContainer = document.querySelector('.messages');
  if (!messagesContainer || !suggestions.length) return;
  const chips = document.createElement('div');
  chips.className = 'suggestion-chips';
  suggestions.slice(0, 4).forEach((suggestion) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = suggestion;
    button.addEventListener('click', () => {
      const messageInput = document.getElementById('message-input');
      if (messageInput) messageInput.value = suggestion;
      sendMessage();
    });
    chips.appendChild(button);
  });
  messagesContainer.appendChild(chips);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function displayCompatibilityResult(result) {
  const messagesContainer = document.querySelector('.messages');
  if (!messagesContainer) return;
  const card = document.createElement('article');
  card.className = 'inline-compatibility-card';
  const status = result.overall_status || (result.compatible ? 'Compatible' : 'Needs attention');
  const itemName = result.game_name || result.name || 'Compatibility check';
  const detailsPage = result.item_type === 'app' ? 'app-details.html' : 'game-details.html';
  const imageUrl = result.image_url || 'assets/placeholder.png';
  card.innerHTML = `
    <div class="compatibility-card-header">
      <div class="compatibility-card-title">
        <img src="${escapeHtml(imageUrl)}" alt="">
        <div><span class="compatibility-card-kicker">Compatibility result</span><strong>${escapeHtml(itemName)}</strong></div>
      </div>
                        <span class="status-badge status-pill ${result.compatible ? 'status-minimum' : 'status-failed'}">${escapeHtml(status)}</span>
    </div>`;
  if (Array.isArray(result.breakdown)) {
    const list = document.createElement('div');
    list.className = 'compatibility-card-grid';
    result.breakdown.forEach((item) => {
      const entry = document.createElement('div');
      const passed = item.status === 'Meets minimum';
      const minimum = result.minimum?.[item.field.toLowerCase()] || 'Minimum not listed';
      const yourPc = result.your_pc?.[item.field.toLowerCase()] || item.status;
      entry.className = `compatibility-check-row${passed ? '' : ' is-failed'}`;
      entry.innerHTML = `<span class="compatibility-check-label"><span class="compatibility-status-icon" aria-hidden="true">${passed ? '✓' : '⚠'}</span> ${escapeHtml(item.field)}</span><span class="compatibility-check-values"><span title="Minimum">${escapeHtml(minimum)}</span><span aria-hidden="true">·</span><span title="Your PC">${escapeHtml(yourPc)}</span></span>`;
      list.appendChild(entry);
    });
    card.appendChild(list);
  }
  const footer = document.createElement('div');
  footer.className = 'compatibility-card-footer';
  footer.innerHTML = `<a class="btn btn-secondary" href="${detailsPage}?id=${encodeURIComponent(result.item_id || '')}">View Details</a>`;
  card.appendChild(footer);
  messagesContainer.appendChild(card);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function displayMentionedItem(item) {
  const messagesContainer = document.querySelector('.messages');
  if (!messagesContainer) return;
  const card = document.createElement('article');
  card.className = 'mentioned-item-card';
  const detailsPage = item.type === 'app' ? 'app-details.html' : 'game-details.html';
  card.innerHTML = `<img src="${escapeHtml(item.image_url || 'assets/placeholder.png')}" alt=""><div><strong>${escapeHtml(item.name)}</strong><a class="btn btn-secondary" href="${detailsPage}?id=${encodeURIComponent(item.id)}">View Details</a></div>`;
  messagesContainer.appendChild(card);
}

function displayMessage(text, sender) {
  const messagesContainer = document.querySelector('.messages');
  if (!messagesContainer) return;

  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${sender}`;
  const messageId = `specter-${sender}-${specterMessageCounter++}`;
  messageDiv.dataset.messageId = messageId;

  if (sender === 'ai') {
    const previousMessage = Array.from(messagesContainer.children).reverse()
      .find((child) => child.classList.contains('message'));
    if (!previousMessage || !previousMessage.classList.contains('ai')) {
      const avatar = document.createElement('img');
      avatar.className = 'specter-avatar-small';
      avatar.src = 'assets/specter-avatar-small.svg';
      avatar.alt = 'Specter';
      messageDiv.appendChild(avatar);
    }
  }

  const bubble = document.createElement('div');
  bubble.className = 'message-bubble';
  if (sender === 'ai') {
    bubble.classList.add('specter-terminal-caret');
    let cursor = 0;
    const stream = () => {
      cursor += 1;
      bubble.textContent = String(text).slice(0, cursor);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
      if (cursor < String(text).length) window.setTimeout(stream, 33);
      else { bubble.classList.remove('specter-terminal-caret'); bubble.innerHTML = formatAssistantText(text); }
    };
    stream();
  } else bubble.innerHTML = escapeHtml(text);
  messageDiv.appendChild(bubble);

  messagesContainer.appendChild(messageDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function formatAssistantText(text) {
  const lines = String(text ?? '').trim().split(/\r?\n/);
  const blocks = [];
  let paragraph = [];
  let bullets = [];

  const flushParagraph = () => {
    if (paragraph.length) {
      blocks.push(`<p>${paragraph.join(' ')}</p>`);
      paragraph = [];
    }
  };
  const flushBullets = () => {
    if (bullets.length) {
      blocks.push(`<ul>${bullets.map((item) => `<li>${formatAssistantLine(item)}</li>`).join('')}</ul>`);
      bullets = [];
    }
  };

  lines.forEach((rawLine) => {
    const line = rawLine.trim();
    if (!line) {
      flushBullets();
      flushParagraph();
      return;
    }
    const bullet = line.match(/^(?:[-*•]|\d+[.)])\s+(.*)$/);
    if (bullet) {
      flushParagraph();
      bullets.push(bullet[1]);
      return;
    }
    flushBullets();
    const heading = line.replace(/:$/, '').trim();
    if (line.endsWith(':') && heading.length <= 70) {
      flushParagraph();
      blocks.push(`<p><strong>${escapeHtml(heading)}</strong></p>`);
      return;
    }
    paragraph.push(formatAssistantLine(line));
  });
  flushBullets();
  flushParagraph();
  return blocks.join('') || '<p></p>';
}

function formatAssistantLine(line) {
  const escaped = escapeHtml(line);
  return escaped.replace(/^([^—:]{2,50})(\s+—\s+)/, '<strong>$1</strong>$2');
}

function showTypingIndicator() {
  const messagesContainer = document.querySelector('.messages');
  if (!messagesContainer) return;

  const typingDiv = document.createElement('div');
  typingDiv.className = 'message ai';
  typingDiv.id = 'typing-indicator';

  const bubble = document.createElement('div');
  bubble.className = 'message-bubble';

  const avatar = document.createElement('img');
  avatar.className = 'specter-avatar-small specter-typing-avatar';
  avatar.src = 'assets/specter-avatar-small.svg';
  avatar.alt = 'Specter is typing';
  typingDiv.appendChild(avatar);

  const typing = document.createElement('div');
  typing.className = 'specter-typing';
  for (let i = 0; i < 3; i++) {
    const dot = document.createElement('span');
    dot.className = 'specter-typing-dot';
    typing.appendChild(dot);
  }

  bubble.appendChild(typing);

  typingDiv.appendChild(bubble);
  messagesContainer.appendChild(typingDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function removeTypingIndicator() {
  const typingIndicator = document.getElementById('typing-indicator');
  if (typingIndicator) {
    typingIndicator.remove();
  }
}


function initBuildAdvisorSuggestion() {
  const resultBox = document.getElementById('build-advisor-result');
  const errorBox = document.getElementById('build-advisor-error');
  const modeTabs = Array.from(document.querySelectorAll('[data-suggestion-mode]'));
  const panels = Array.from(document.querySelectorAll('[data-suggestion-panel]'));
  const budgetForm = document.getElementById('build-advisor-form');
  const budgetButton = document.getElementById('get-build-suggestion');
  const gameForm = document.getElementById('build-advisor-game-form');
  const gameButton = document.getElementById('get-game-suggestion');
  const tierButtons = Array.from(document.querySelectorAll('[data-tier-option]'));

  if (!resultBox || !errorBox || (!budgetForm && !gameForm)) return;

  let selectedTier = 'minimum';
  let buildAdvisorCatalog = [];
  let buildAdvisorCatalogReady = Promise.resolve();

  const gameInput = document.getElementById('game-suggestion-input');
  let gameSuggestionList = null;
  if (gameInput && window.CompatiXSearch) {
    gameInput.parentElement?.classList.add('autocomplete-container');
    gameSuggestionList = document.createElement('ul');
    gameSuggestionList.id = 'build-advisor-suggestions';
    gameSuggestionList.className = 'autocomplete-list';
    gameSuggestionList.setAttribute('aria-label', 'Matching games and apps');
    gameInput.after(gameSuggestionList);
    buildAdvisorCatalogReady = window.CompatiXSearch.loadCatalog()
      .then((catalog) => { buildAdvisorCatalog = catalog; })
      .catch(() => { buildAdvisorCatalog = []; });

    let suggestionTimer = null;
    const hideSuggestions = () => gameSuggestionList?.classList.remove('active');
    gameInput.addEventListener('input', () => {
      window.clearTimeout(suggestionTimer);
      const value = gameInput.value.trim();
      if (!value) { hideSuggestions(); return; }
      suggestionTimer = window.setTimeout(async () => {
        await buildAdvisorCatalogReady;
        const matches = window.CompatiXSearch.rank(value, buildAdvisorCatalog, 8);
        gameSuggestionList.innerHTML = '';
        if (!matches.length) {
          const empty = document.createElement('li');
          empty.className = 'no-match';
          empty.textContent = 'No matching titles found';
          gameSuggestionList.appendChild(empty);
        } else {
          matches.forEach((item) => {
            const option = document.createElement('li');
            option.textContent = item.name;
            option.addEventListener('click', () => {
              gameInput.value = item.name;
              hideSuggestions();
            });
            gameSuggestionList.appendChild(option);
          });
        }
        gameSuggestionList.classList.add('active');
      }, 170);
    });
    document.addEventListener('click', (event) => {
      if (event.target !== gameInput && !gameSuggestionList.contains(event.target)) hideSuggestions();
    });
  }

  const prefilter = new URLSearchParams(window.location.search);
  const prefilterComponent = prefilter.get('component');
  const prefilterTier = prefilter.get('tier');
  if (prefilterComponent && prefilterTier) {
    fetch('parts.json')
      .then(response => response.json())
      .then(parts => {
        const part = parts?.[prefilterComponent.toLowerCase()];
        if (!part) return;
        const note = document.createElement('p');
        note.className = 'build-advisor-prefilter';
        note.textContent = `Showing ${prefilterTier} guidance for ${prefilterComponent.toUpperCase()}: ${part[prefilterTier] || part.minimum}.`;
        document.getElementById('build-advisor-suggestion-panel')?.prepend(note);
      })
      .catch(() => {});
    selectedTier = prefilterTier === 'recommended' ? 'best' : 'minimum';
  }

  const setSuggestionMode = (mode) => {
    modeTabs.forEach((tab) => {
      const selected = tab.dataset.suggestionMode === mode;
      tab.classList.toggle('is-active', selected);
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
    panels.forEach((panel) => {
      const visible = panel.dataset.suggestionPanel === mode;
      panel.classList.toggle('is-hidden', !visible);
    });
    errorBox.hidden = true;
  };

  modeTabs.forEach((tab) => {
    tab.addEventListener('click', () => setSuggestionMode(tab.dataset.suggestionMode));
  });

  tierButtons.forEach((button) => {
    button.addEventListener('click', () => {
      selectedTier = button.dataset.tierOption || 'minimum';
      tierButtons.forEach((card) => {
        const isSelected = card === button;
        card.classList.toggle('is-selected', isSelected);
        card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
    });
  });

  const showSuggestion = (cpu, gpu, ram, storage, rationale, context = null) => {
    document.getElementById('suggested-cpu').textContent = cpu || '—';
    document.getElementById('suggested-gpu').textContent = gpu || '—';
    document.getElementById('suggested-ram').textContent = ram || '—';
    document.getElementById('suggested-storage').textContent = storage || '—';
    document.getElementById('suggested-rationale').textContent = rationale || 'No rationale provided.';

    const contextWrap = document.getElementById('suggested-context');
    const contextImage = document.getElementById('suggested-context-image');
    const contextName = document.getElementById('suggested-context-name');
    if (context && context.name) {
      contextWrap.classList.remove('is-hidden');
      contextName.textContent = context.name;
      if (contextImage) {
        if (context.image) {
          contextImage.src = context.image;
          contextImage.alt = context.name;
          contextImage.style.display = 'block';
        } else {
          contextImage.style.display = 'none';
        }
      }
    } else {
      contextWrap.classList.add('is-hidden');
    }

    resultBox.classList.remove('is-hidden');
    resultBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  };

  const handleRequestError = (message) => {
    errorBox.hidden = false;
    errorBox.textContent = message;
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  };

  const budgetSubmit = async (event) => {
    event.preventDefault();
    const budget = document.getElementById('budget')?.value || '';
    const usecase = document.getElementById('usecase')?.value || '';

    if (!budget || !usecase) {
      handleRequestError('Please select both a budget range and a use case.');
      return;
    }

    if (budgetButton) {
      budgetButton.disabled = true;
      budgetButton.textContent = 'Generating...';
    }
    errorBox.hidden = true;

    try {
      const response = await fetch('build_advisor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ budget, usecase }),
      });

      const data = await response.json();
      if (!response.ok || data.error) throw new Error(data.error || 'Failed to generate build');

      showSuggestion(data.cpu_tier, data.gpu_tier, data.ram, data.storage, data.rationale);
    } catch (error) {
      handleRequestError(error.message || 'Unable to generate suggestion.');
    } finally {
      if (budgetButton) {
        budgetButton.disabled = false;
        budgetButton.textContent = 'Get Suggestion';
      }
    }
  };

  const gameSubmit = async (event) => {
    event.preventDefault();
    await buildAdvisorCatalogReady;
    const typedGameName = gameInput?.value.trim() || '';
    const bestMatch = window.CompatiXSearch?.rank(typedGameName, buildAdvisorCatalog, 1)?.[0];
    const gameName = bestMatch?.name || typedGameName;
    if (bestMatch && gameInput) gameInput.value = bestMatch.name;

    if (!gameName) {
      handleRequestError('Enter a game or app name to get a suggestion.');
      return;
    }

    if (gameButton) {
      gameButton.disabled = true;
      gameButton.textContent = 'Finding a build…';
    }
    errorBox.hidden = true;

    try {
      const response = await fetch(`get_requirements.php?game=${encodeURIComponent(gameName)}`);
      const responseText = await response.text();
      let payload = [];
      try {
        payload = JSON.parse(responseText);
      } catch (parseError) {
        throw new Error('The game/app requirement service returned an invalid response.');
      }
      if (!response.ok || !Array.isArray(payload) || !payload.length) {
        throw new Error('No match was found for that game or app. Try a different title.');
      }

      const gameData = payload[0];
      let cpu = gameData.min_cpu || '—';
      let gpu = gameData.min_gpu || '—';
      let ram = gameData.min_ram ? `${gameData.min_ram}GB` : '—';
      let storage = gameData.min_storage ? `${gameData.min_storage}GB` : '—';
      let rationale = `This ${selectedTier} tier recommendation is based on ${gameData.name || gameName}.`;

      if (selectedTier === 'balanced' || selectedTier === 'best') {
        cpu = gameData.rec_cpu || gameData.min_cpu || cpu;
        gpu = gameData.rec_gpu || gameData.min_gpu || gpu;
        ram = gameData.rec_ram ? `${gameData.rec_ram}GB` : ram;
        storage = gameData.rec_storage ? `${gameData.rec_storage}GB` : storage;
      }

      if (selectedTier === 'best') {
        rationale = `This best-tier recommendation aims for a premium experience for ${gameData.name || gameName} with stronger headroom for future updates and higher settings.`;
      } else if (selectedTier === 'balanced') {
        rationale = `This balanced-tier recommendation targets smooth performance for ${gameData.name || gameName} without overspending on the highest-end hardware.`;
      }

      const fallbackNote = gameData.note ? ` ${gameData.note}` : '';
      const context = {
        name: gameData.name || gameName,
        image: gameData.background_image || gameData.image_background || gameData.image || ''
      };
      showSuggestion(cpu, gpu, ram, storage, `${rationale}${fallbackNote}`, context);
    } catch (error) {
      handleRequestError(error.message || 'Unable to generate a game/app suggestion.');
    } finally {
      if (gameButton) {
        gameButton.disabled = false;
        gameButton.textContent = 'Get Suggestion';
      }
    }
  };

  if (budgetForm) budgetForm.addEventListener('submit', budgetSubmit);
  if (gameForm) gameForm.addEventListener('submit', gameSubmit);
  setSuggestionMode('budget');
}

function prefillBuildAdvisorSpecs() {
  const form = document.getElementById('check-form');
  if (!form) return;
  try {
    const specs = JSON.parse(sessionStorage.getItem('buildAdvisorSpecs') || 'null');
    if (!specs) return;
    Object.entries(specs).forEach(([key, value]) => {
      const input = document.getElementById(key);
      if (input && Array.from(input.options).some((option) => option.value === value)) {
        input.value = value;
      }
    });
    sessionStorage.removeItem('buildAdvisorSpecs');
  } catch (error) {
    sessionStorage.removeItem('buildAdvisorSpecs');
  }
}


// =============================================================================
// 7. CONTACT FORM
// =============================================================================

function validateContactForm() {
  const name = document.getElementById('name')?.value.trim();
  const email = document.getElementById('email')?.value.trim();
  const subject = document.getElementById('subject')?.value.trim();
  const message = document.getElementById('message')?.value.trim();

  const errors = [];

  if (!name) errors.push('Name is required');
  if (!email) {
    errors.push('Email is required');
  } else if (!isValidEmail(email)) {
    errors.push('Email is not valid');
  }
  if (!subject) errors.push('Subject is required');
  if (!message) errors.push('Message is required');

  if (errors.length > 0) {
    showAlert(errors.join(', '), 'error');
    return false;
  }

  return true;
}

async function submitContactForm(e) {
  e.preventDefault();

  if (!validateContactForm()) {
    return;
  }

  const formData = new FormData(document.getElementById('contact-form'));
  const submitButton = document.querySelector('#contact-form button[type="submit"]');
  setLoading(submitButton, true, 'Sending message');
  const data = {
    name: formData.get('name'),
    email: formData.get('email'),
    subject: formData.get('subject'),
    message: formData.get('message'),
  };

  try {
    const response = await fetch('contact.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error(`Contact request failed: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      showAlert('Thank you! Your message has been sent successfully.', 'success');
      document.getElementById('contact-form').reset();
    } else {
      showAlert('An error occurred: ' + (result.message || 'Please try again.'), 'error');
    }
  } catch (error) {
    showAlert('An error occurred while sending your message. Please try again.', 'error');
  } finally {
    setLoading(submitButton, false);
  }
}


// =============================================================================
// 8. PAGE INITIALIZATION & EVENT LISTENERS
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {
  initPageLoader();
  initHeroParallax();
  buildCompatixContext();

  initNavigation();
  initScrollAnimations();
  initThemeToggle();
  addBuildAdvisorNavLink();
  initGlossaryTooltips();

  const queryParams = new URLSearchParams(window.location.search);
  const queryGame = queryParams.get('item') || queryParams.get('game') || queryParams.get('app');
  const gameInput = document.getElementById('game-input');

  if (document.getElementById('game-input')) {
    initAutocomplete();
    if (gameInput && queryGame) {
      selectGame(queryGame.trim());
    }
  }

  bindHelpModalTriggers();

  initChat();
  initHomeSpecterPrompts();
  initAssistantFeatures();

  initBuildAdvisorSuggestion();
  prefillBuildAdvisorSpecs();

  const checkForm = document.getElementById('check-form');
  if (checkForm) {
    checkForm.addEventListener('submit', submitCheckForm);
  }
  initCheckerWizard();

  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', submitContactForm);
  }

  document.querySelectorAll('[data-clear-library]').forEach((button) => button.addEventListener('click', () => {
    document.getElementById('clear-filters')?.click();
  }));

  document.querySelectorAll('[data-filter-toggle]').forEach((button) => button.addEventListener('click', () => {
    const sidebar = button.parentElement.querySelector('.filter-sidebar');
    const open = sidebar?.classList.toggle('is-open');
    button.setAttribute('aria-expanded', String(Boolean(open)));
  }));

  document.getElementById('copy-results-btn')?.addEventListener('click', () => {
    const result = window.currentCompatibilityResult || JSON.parse(sessionStorage.getItem('compatibilityResult') || 'null');
    if (result) copyCompatibilityResult(result);
    else showToast('No compatibility result is available to copy.', 'error');
  });

  initFloatingSpecter();
  initContextActions();
  initDetailsCarousels();
  normalizeDetailsFallbacks();
});

function openLightbox(images, startIndex = 0) {
  const validImages = (Array.isArray(images) ? images : []).filter(Boolean);
  if (!validImages.length) return;

  let lightbox = document.getElementById('compatix-lightbox');
  if (!lightbox) {
    lightbox = document.createElement('div');
    lightbox.id = 'compatix-lightbox';
    lightbox.className = 'compatix-lightbox';
    lightbox.hidden = true;
    lightbox.innerHTML = `
      <button class="compatix-lightbox-close" type="button" aria-label="Close screenshot">&times;</button>
      <button class="compatix-lightbox-arrow" type="button" data-direction="prev" aria-label="Previous screenshot">&#8249;</button>
      <figure class="compatix-lightbox-frame">
        <img alt="Expanded screenshot">
        <figcaption class="compatix-lightbox-counter" aria-live="polite"></figcaption>
      </figure>
      <button class="compatix-lightbox-arrow" type="button" data-direction="next" aria-label="Next screenshot">&#8250;</button>`;
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Screenshot viewer');
    document.body.appendChild(lightbox);
  }

  const closeButton = lightbox.querySelector('.compatix-lightbox-close');
  const image = lightbox.querySelector('img');
  const counter = lightbox.querySelector('.compatix-lightbox-counter');
  let current = (Number(startIndex) + validImages.length) % validImages.length;
  const previousOverflow = document.body.style.overflow;

  const showImage = (index) => {
    current = (index + validImages.length) % validImages.length;
    image.src = validImages[current];
    image.alt = `Expanded screenshot ${current + 1} of ${validImages.length}`;
    counter.textContent = `${current + 1} / ${validImages.length}`;
  };
  const close = () => {
    lightbox.classList.remove('is-open');
    lightbox._closeTimer = window.setTimeout(() => {
      lightbox.hidden = true;
      document.body.style.overflow = previousOverflow;
    }, 180);
    document.removeEventListener('keydown', onKeydown);
  };
  const onKeydown = (event) => {
    if (event.key === 'Escape') close();
    if (event.key === 'ArrowLeft') showImage(current - 1);
    if (event.key === 'ArrowRight') showImage(current + 1);
  };

  showImage(current);
  window.clearTimeout(lightbox._closeTimer);
  lightbox.hidden = false;
  document.body.style.overflow = 'hidden';
  requestAnimationFrame(() => lightbox.classList.add('is-open'));
  closeButton.onclick = close;
  lightbox.querySelector('[data-direction="prev"]').onclick = () => showImage(current - 1);
  lightbox.querySelector('[data-direction="next"]').onclick = () => showImage(current + 1);
  lightbox.onclick = (event) => {
    if (event.target === lightbox) close();
  };
  window.setTimeout(() => closeButton.focus(), 0);
  document.addEventListener('keydown', onKeydown);
}

window.openLightbox = openLightbox;

function renderGallery(container, urls, titleName = 'Title') {
  if (typeof container === 'string') {
    container = document.getElementById(container);
  }
  if (!container) return;

  const images = [...new Set((Array.isArray(urls) ? urls : []).filter(Boolean))].slice(0, 6);
  if (!images.length) {
    container.innerHTML = '<p class="muted">Screenshots are not available for this item.</p>';
    return;
  }

  let currentIndex = 0;

  const updateGallery = () => {
    const mainImg = container.querySelector('.gallery-main-img');
    const counterText = container.querySelector('.gallery-counter-text');
    const thumbs = container.querySelectorAll('.gallery-thumb-btn');

    if (mainImg) {
      mainImg.src = images[currentIndex];
      mainImg.alt = `${titleName} screenshot ${currentIndex + 1}`;
    }
    if (counterText) {
      counterText.textContent = `${currentIndex + 1} / ${images.length}`;
    }
    thumbs.forEach((thumb, idx) => {
      if (idx === currentIndex) {
        thumb.classList.add('active');
        thumb.setAttribute('aria-current', 'true');
      } else {
        thumb.classList.remove('active');
        thumb.removeAttribute('aria-current');
      }
    });
  };

  container.innerHTML = `
    <div class="gallery-container">
      <div class="gallery-main-view" aria-label="Main screenshot preview">
        <img class="gallery-main-img" src="${escapeHtml(images[0])}" alt="${escapeHtml(titleName)} screenshot 1">
        <div class="gallery-position-badge">
          <span class="gallery-counter-text">1 / ${images.length}</span>
        </div>
      </div>
      <div class="gallery-thumbnails-grid" role="tablist" aria-label="Screenshot thumbnails">
        ${images.map((url, idx) => `
          <button type="button" class="gallery-thumb-btn ${idx === 0 ? 'active' : ''}" data-index="${idx}" aria-label="View screenshot ${idx + 1} of ${images.length}">
            <img src="${escapeHtml(url)}" alt="${escapeHtml(titleName)} thumbnail ${idx + 1}" loading="lazy" decoding="async" onerror="this.closest('.gallery-thumb-btn').remove()">
          </button>
        `).join('')}
      </div>
    </div>
  `;

  container.querySelectorAll('.gallery-thumb-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      currentIndex = parseInt(btn.getAttribute('data-index'), 10);
      updateGallery();
    });
  });

  const mainView = container.querySelector('.gallery-main-view');
  if (mainView) {
    mainView.addEventListener('click', () => {
      if (typeof openLightbox === 'function') {
        openLightbox(images, currentIndex);
      }
    });
  }
}

window.renderGallery = renderGallery;
window.renderDetailsCarousel = renderGallery;

function initDetailsCarousels() {
  document.querySelectorAll('.screenshot-rail').forEach((rail) => {
    const enhance = () => {
      const images = [...rail.querySelectorAll('.screenshot-thumb img')].map((image) => image.src);
      if (images.length && !rail.dataset.carouselReady) {
        rail.dataset.carouselReady = 'true';
        renderGallery(rail, images);
      }
    };
    new MutationObserver(enhance).observe(rail, { childList: true });
    enhance();
  });
}

function normalizeDetailsFallbacks() {
  document.querySelectorAll('.requirements-card span').forEach((value) => {
    if (!value.textContent.trim() || value.textContent.trim() === 'Not specified') value.textContent = '—';
  });
}
