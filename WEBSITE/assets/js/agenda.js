/* Agenda public : les dates restent visibles dans le HTML si l’API est indisponible. */
(() => {
  const locale = document.documentElement.lang === 'en' ? 'en-GB' : 'fr-FR';
  const copy = document.documentElement.lang === 'en'
    ? { residence: 'Residency', diffusion: 'Performance', empty: 'No dates are currently scheduled.' }
    : { residence: 'Résidence', diffusion: 'Diffusion', empty: 'Aucune date programmée pour le moment.' };
  const date = value => new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(`${value}T12:00:00`));
  const escape = value => String(value || '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[c]));
  const eventText = item => {
    const english = locale === 'en-GB';
    const title = english ? (item.titleEn || item.titleFr) : item.titleFr;
    const details = english ? (item.detailsEn || item.detailsFr) : item.detailsFr;
    return `${escape(title)} — ${escape(details)}`;
  };
  const row = (item, label) => `<div class="agenda-row"><span class="agenda-date">${escape(date(item.startDate))}${item.endDate ? ` – ${escape(date(item.endDate))}` : ''}</span><span>${eventText(item)}</span>${label ? `<span class="agenda-place">${escape(label)}</span>` : ''}</div>`;
  fetch('/api/index.php?action=events', { credentials: 'same-origin' })
    .then(response => response.ok ? response.json() : Promise.reject())
    .then(({ events }) => {
      const lists = document.querySelectorAll('[data-agenda-list]');
      if (!lists.length) return;
      const today = new Date().toISOString().slice(0, 10);
      lists.forEach(list => {
        const kind = list.dataset.agendaList;
        let items = events.filter(item => kind === 'upcoming' ? item.featured && item.startDate >= today : item.type === kind);
        if (kind !== 'upcoming') items = items.sort((a, b) => b.startDate.localeCompare(a.startDate));
        else items = items.sort((a, b) => a.startDate.localeCompare(b.startDate));
        list.innerHTML = items.length ? items.map(item => row(item, kind === 'upcoming' ? copy[item.type] : (locale === 'en-GB' ? (item.placeEn || item.placeFr) : item.placeFr))).join('') : `<p>${copy.empty}</p>`;
      });
    })
    .catch(() => {});
})();
