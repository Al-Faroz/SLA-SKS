/* Tema visual SLA-SKS. Sinkron sebelum CSS agar default gelap tidak berkedip. */
(() => {
  'use strict';
  const html = document.documentElement;
  const getSaved = (key) => { try { return localStorage.getItem(key); } catch (_) { return null; } };
  const save = (key, value) => { try { localStorage.setItem(key, value); } catch (_) { /* Browser membatasi storage. */ } };
  const current = getSaved('sla-sks-tema') === 'light' ? 'light' : 'dark';
  html.dataset.theme = current;

  document.addEventListener('DOMContentLoaded', () => {
    const buttons = [...document.querySelectorAll('[data-theme-toggle]')];
    function useTheme(next) {
      const light = next === 'light';
      html.dataset.theme = light ? 'light' : 'dark';
      buttons.forEach((btn) => {
        btn.setAttribute('aria-label', light ? 'Aktifkan tema gelap' : 'Aktifkan tema terang');
        btn.setAttribute('title', light ? 'Aktifkan tema gelap' : 'Aktifkan tema terang');
        btn.setAttribute('aria-pressed', String(light));
      });
    }
    useTheme(html.dataset.theme);
    buttons.forEach((btn) => btn.addEventListener('click', () => {
      const next = html.dataset.theme === 'dark' ? 'light' : 'dark';
      useTheme(next);
      save('sla-sks-tema', next);
    }));

    const panel = document.querySelector('[data-shell="panel"]');
    const collapse = panel?.querySelector('[data-sidebar-collapse]');
    if (!panel || !collapse) return;
    const mobile = window.matchMedia('(max-width: 767px)');
    let collapsed = getSaved('sla-sks-sidebar') === 'collapsed';
    function renderSidebar() {
      const active = collapsed && !mobile.matches;
      panel.classList.toggle('a-app--is-collapsed', active);
      collapse.setAttribute('aria-expanded', String(!active));
      collapse.setAttribute('aria-label', active ? 'Perluas sidebar' : 'Minimalkan sidebar');
      collapse.setAttribute('title', active ? 'Perluas sidebar' : 'Minimalkan sidebar');
    }
    collapse.addEventListener('click', () => {
      collapsed = !collapsed;
      renderSidebar();
      save('sla-sks-sidebar', collapsed ? 'collapsed' : 'expanded');
    });
    mobile.addEventListener('change', renderSidebar);
    renderSidebar();
  });
})();
