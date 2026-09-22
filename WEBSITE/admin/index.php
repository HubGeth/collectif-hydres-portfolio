<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Administration — Collectif Hydres</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <main class="shell">
    <section id="login-view" class="card login-card">
      <p class="eyebrow">Collectif Hydres</p>
      <h1>Administration</h1>
      <p>Accès réservé au collectif.</p>
      <form id="login-form">
        <label>Identifiant<input name="username" autocomplete="username" required></label>
        <label>Mot de passe<input name="password" type="password" autocomplete="current-password" required></label>
        <p class="error" id="login-error" aria-live="polite"></p>
        <button>Se connecter</button>
      </form>
    </section>

    <section id="admin-view" hidden>
      <header class="topbar"><div><p class="eyebrow">Collectif Hydres</p><h1>Contenu du site</h1></div><button class="secondary" id="logout">Se déconnecter</button></header>
      <div class="notice">Les modifications sont publiées immédiatement sur le site. Les champs anglais sont facultatifs ; à défaut, le français est affiché.</div>
      <div class="layout">
        <section class="card">
          <h2 id="form-title">Ajouter une date</h2>
          <form id="event-form">
            <input type="hidden" name="id">
            <div class="twocol"><label>Date de début<input name="startDate" type="date" required></label><label>Date de fin <small>(facultatif)</small><input name="endDate" type="date"></label></div>
            <div class="twocol"><label>Type<select name="type"><option value="diffusion">Diffusion</option><option value="residence">Résidence</option></select></label><label class="check"><input name="featured" type="checkbox"> Afficher dans « Prochaines dates »</label></div>
            <label>Titre — français<input name="titleFr" required placeholder="ex. Automne Curieux"></label>
            <label>Détail — français<input name="detailsFr" required placeholder="ex. Geschwister — Hangar 107, Rouen (15h)"></label>
            <label>Lieu court — français<input name="placeFr" placeholder="ex. Rouen"></label>
            <details><summary>Version anglaise (facultative)</summary><label>Title<input name="titleEn"></label><label>Details<input name="detailsEn"></label><label>Place<input name="placeEn"></label></details>
            <p class="error" id="event-error" aria-live="polite"></p>
            <div class="actions"><button id="save-event">Enregistrer la date</button><button type="button" class="secondary" id="cancel-edit" hidden>Annuler</button></div>
          </form>
        </section>
        <section class="card"><h2>Dates enregistrées</h2><div id="events" class="events"></div></section>
      </div>
      <section class="card media-card"><h2>Photos</h2><p>JPG, PNG ou WebP, jusqu’à 5 Mo. Après l’envoi, copiez l’URL pour l’utiliser dans une image du site.</p><form id="upload-form"><input name="image" type="file" accept="image/jpeg,image/png,image/webp" required><button>Envoyer la photo</button></form><p class="error" id="upload-error" aria-live="polite"></p><div id="media" class="media"></div></section>
      <section class="card media-card"><h2>Créations</h2><p>Chaque création apparaît automatiquement sur l’accueil, la page Créations et dans son sous-menu. Les URLs de photos sont disponibles dans la médiathèque ci-dessus.</p><div id="creations-admin" class="events"></div><form id="creation-form"><input type="hidden" name="id"><div class="twocol"><label>Titre français<input name="titleFr" required></label><label>Slug <small>(sans espace, ex. ma-piece)</small><input name="slug" pattern="[a-z0-9-]+" required></label></div><label>Métadonnées françaises<input name="metaFr" placeholder="Duo - 40 min - Création 2028"></label><label>Texte court français<input name="introFr" required></label><label>Texte de présentation français<textarea name="bodyFr"></textarea></label><label>Photo principale (URL)<input name="hero" required></label><label>Photos de la création <small>(une URL par ligne)</small><textarea name="photos"></textarea></label><details><summary>Version anglaise (facultative)</summary><label>Title<input name="titleEn"></label><label>Metadata<input name="metaEn"></label><label>Short text<input name="introEn"></label><label>Presentation<textarea name="bodyEn"></textarea></label></details><p class="error" id="creation-error"></p><div class="actions"><button>Enregistrer la création</button><button type="button" class="secondary" id="cancel-creation" hidden>Annuler</button></div></form></section>
      <section class="card media-card"><h2>Contenus partagés</h2><form id="shared-form"><label>Photos « Un collectif tout terrain » <small>(une URL par ligne ; mêmes photos sur Accueil et Le collectif)</small><textarea name="collectivePhotos" rows="5"></textarea></label><label>« Ils nous ont accueillis » — Médiation <small>(une ligne par information)</small><textarea name="mediationHosts" rows="6"></textarea></label><p class="error" id="shared-error"></p><button>Enregistrer ces contenus</button></form></section>
    </section>
  </main>
  <script src="admin.js"></script>
</body>
</html>
