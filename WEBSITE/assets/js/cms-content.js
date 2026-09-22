(() => {
  const root = '';
  const local = document.documentElement.lang === 'en' ? 'en' : 'fr';
  const escape = value => String(value || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const field = (item, name) => local === 'en' ? (item[`${name}En`] || item[`${name}Fr`] || '') : (item[`${name}Fr`] || '');
  const creationUrl = item => `${root}creation.html?slug=${encodeURIComponent(item.slug)}`;
  const card = item => `<a class="card reveal" href="${creationUrl(item)}" style="background:transparent;"><div class="card-photo"><img class="ph" src="${escape(item.hero)}" alt="${escape(field(item,'title'))}"></div><div class="card-body"><div class="card-meta"><span class="meta">${escape(field(item,'meta'))}</span></div><h3>${escape(field(item,'title'))}</h3><p style="opacity:.75;">${escape(field(item,'intro'))}</p></div></a>`;
  fetch('/api/index.php?action=public-content').then(r => r.ok ? r.json() : Promise.reject()).then(data => {
    document.querySelectorAll('[data-creations-list]').forEach(node => node.innerHTML = data.creations.map(card).join(''));
    document.querySelectorAll('[data-creations-nav]').forEach(node => {
      node.innerHTML = data.creations.map(item => `<a href="${creationUrl(item)}">${escape(field(item,'title'))}</a>`).join('');
    });
    document.querySelectorAll('[data-collective-gallery]').forEach(node => node.innerHTML = data.collectivePhotos.map(url => `<img class="ph" src="${escape(url)}" alt="">`).join(''));
    document.querySelectorAll('[data-mediation-hosts]').forEach(node => node.innerHTML = data.mediationHosts.map(item => `<li>${escape(item)}</li>`).join(''));
  }).catch(() => {});
})();
