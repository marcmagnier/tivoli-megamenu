# Tivoli Mega Menu

Plugin WordPress qui ajoute un mega menu « maître-détail » au site du CHU Tivoli (https://www.chu-tivoli.be/), un site WordPress sous Divi.

Le menu est entièrement géré depuis **Apparence › Menus**. Il s'affiche avec le shortcode `[tivoli_megamenu]`, placé dans le header global du **Divi Theme Builder**.

> Statut : **v0.1.2, prototype**. Installé sur https://tivoli.digima.be/ (visible connecté) ; premiers essais sur Divi en cours.

---

## 1. Contexte et objectif

Le mega menu actuel « Offre de soins » affiche environ 40 liens en même temps : 6 colonnes, tout en majuscules, tout dans le même rouge. Plusieurs liens sont en double, par exemple « Où se présenter ? » apparaît trois fois. Il n'y a ni hiérarchie visuelle ni point d'accroche. Le client envisage en plus d'ajouter un niveau de profondeur.

Trois pistes ont été maquettées. La **piste A** a été retenue.

- Maquettes (canvas Design) : https://claude.ai/artifact/AfcXccutzrkVpomtPsf9dC
  - **A · Maître-détail** (retenue) : rubriques à gauche, détail de la rubrique au centre, encart contextuel et accès directs à droite, recherche en pied de panneau.
  - **B · Colonnes allégées** : non retenue. Elle reste une alternative si le client refuse le changement de structure.
  - **C · Mobile en tiroirs** : sert de référence pour le comportement mobile de la piste A.

### Principes de design à respecter

- Une seule rubrique détaillée à la fois. On ne montre jamais les 40 liens d'un coup.
- Les pages sont regroupées **par étape du parcours** (Avant / Pendant / Après), et non en listes à plat.
- Le **3e niveau** s'affiche en petits liens indentés sous leur page parente, **jamais en sous-menu qui s'ouvre au survol**.
- Les liens sont en casse normale. Les majuscules sont réservées aux petits libellés (surtitres, titres de groupe).
- Le rouge est réservé aux états actifs et aux urgences. Les encarts standards sont en pétrole.
- Aucun libellé en double. Les infos d'accès communes vont sur une seule page.

---

## 2. Structure du dépôt

```
tivoli-megamenu/
├── tivoli-megamenu.php     # Plugin : emplacements de menu, champs des éléments, arbre, rendu (shortcode)
├── assets/
│   ├── megamenu.css        # Styles (desktop, écrans moyens, mobile ≤ 980 px), variables CSS
│   └── megamenu.js         # Comportement : ouverture, survol avec délai, clavier, tiroirs mobile (vanilla JS)
├── README.md
└── CLAUDE.md               # (à créer) consignes pour Claude Code
```

Le plugin n'a aucune dépendance : pas de jQuery, pas de build, pas de Composer.

---

## 3. Fonctionnement

### Emplacements de menu

| Emplacement | Rôle | Obligatoire |
|---|---|---|
| `tmm_main`: Mega menu – navigation principale | Barre principale et panneaux | Oui |
| `tmm_quick`: Mega menu – accès directs | Liens sous l'encart, colonne de droite | Non |
| `tmm_popular`: Mega menu – recherches fréquentes | Liens « chips » dans le pied du panneau | Non |

### Convention de profondeur (Apparence › Menus)

| Niveau | Rôle | Exemple |
|---|---|---|
| 1 | Entrée de la barre. Si l'élément a des enfants, il ouvre un panneau. | Offre de soins |
| 2 | Rubrique de la colonne de gauche | Hospitalisation |
| 3 | Page, **ou** titre de groupe si « Titre de groupe » est coché | Avant le séjour |
| 4 | Pages d'un groupe, ou 3e niveau sous une page | Que faut-il emporter ? |
| 5 | 3e niveau sous une page d'un groupe | Unités de soins › … |

- Un titre de groupe avec l'URL `#` s'affiche comme un simple intertitre, non cliquable.
- Les éléments de niveau 3 qui ne sont pas des groupes, s'ils se suivent, forment un groupe sans titre.
- Si l'URL d'un élément de niveau 1 ou 2 n'est pas `#`, le panneau ajoute un lien vers sa page d'accueil.

### Champs ajoutés aux éléments de menu

Ils sont stockés en post meta sur l'élément de menu, avec le préfixe `_tmm_`.

| Clé | Type | Utilisé sur |
|---|---|---|
| `_tmm_group` | case à cocher | niveau 3 |
| `_tmm_feat_kicker`, `_tmm_feat_title`, `_tmm_feat_text` | texte | niveau 2 (encart) |
| `_tmm_feat_cta`, `_tmm_feat_url` | texte, URL | niveau 2 (bouton de l'encart) |
| `_tmm_feat_tone` | `teal` ou `red` | niveau 2 |

La **description native** de l'élément de niveau 2 sert de sous-titre à la rubrique. Elle s'active dans Options de l'écran › Description.

### Principaux hooks et fonctions

- `wp_nav_menu_item_custom_fields` et `wp_update_nav_menu_item` : affichent et enregistrent les champs, avec un nonce par élément et la capacité `edit_theme_options`.
- `tmm_tree( $location )` : construit l'arbre parent → enfants. La propriété `$item->tmm_children` contient les enfants.
- `tmm_groups( $rubrique )` : regroupe les enfants d'une rubrique en groupes.
- `tmm_render()` : callback du shortcode `[tivoli_megamenu]`.

---

## 4. Environnement de développement

### Prérequis

- WordPress 5.4 ou plus (pour le hook `wp_nav_menu_item_custom_fields`) et PHP 7.4 ou plus.
- **Divi**, thème sous licence, à installer à la main. Il n'est pas versionné dans ce dépôt.
- Un WordPress local au choix : LocalWP, wp-env (`npx @wordpress/env start`) ou Docker.

### Installation locale

```bash
# Exemple avec wp-env (le dossier du plugin est monté automatiquement)
npx @wordpress/env start
# Puis : installer et activer Divi, activer « Tivoli Mega Menu »
```

### Jeu de données de test

Recréer dans Apparence › Menus le menu « Offre de soins » du site actuel. Il comprend 6 rubriques : Consultations, Hospitalisation, Hospitalisation de jour, Imagerie et prélèvements, Urgences, Pôle mère-enfant. Ajouter au moins :

- un groupe avec l'URL `#` et un groupe avec une URL ;
- une page avec des enfants (3e niveau) ;
- un encart rouge (Urgences) et un encart pétrole.

> À faire : un script WP-CLI (`bin/seed-menu.php`) qui crée ce menu de test automatiquement.

---

## 5. Intégration dans Divi

1. Ouvrir Divi › Theme Builder › en-tête global et remplacer le module **Menu** par un module **Code** contenant `[tivoli_megamenu]`.
2. Garder la section du header en `position: relative`, ce qui est le défaut chez Divi. Le panneau prend la largeur de cette section.
3. Sur la section et la ligne, régler Avancé › Visibilité › Débordement sur **Visible** et mettre un z-index plus élevé que le contenu.
4. Sur mobile, régler `--tmm-offset` sur la hauteur réelle du header.
5. Si un parent du menu a un `transform` (cas de certains effets « sticky » de Divi), les panneaux mobiles en `position: fixed` se placeront mal. À vérifier en priorité.

### Variables CSS

À surcharger dans Divi › Options du thème › CSS personnalisé :

```css
.tmm {
  --tmm-red: #C8161D;
  --tmm-red-soft: #FDECEC;
  --tmm-teal: #1E4E5A;
  --tmm-ink: #1F2933;
  --tmm-muted: #5B6670;
  --tmm-max: 1280px;   /* largeur du contenu du panneau */
  --tmm-offset: 80px;  /* mobile : hauteur du header */
  --tmm-z: 9990;
}
```

Les couleurs viennent du site actuel et ont été choisies pour un contraste d'au moins 4.5:1 sur fond blanc. Elles sont à confirmer avec la charte officielle du CHU.

---

## 6. Accessibilité (exigences)

- Les déclencheurs sont de vrais `<button>`, avec `aria-expanded` et `aria-controls`. Les liens sont de vrais `<a href>`.
- **Échap** referme le panneau et rend le focus au déclencheur.
- Le panneau se referme quand le focus clavier sort de la navigation.
- **↑ et ↓** parcourent les rubriques ; **→** entre dans les liens du détail.
- `aria-current="page"` est posé sur l'élément de la page en cours, et l'entrée de niveau 1 qui le contient est mise en évidence.
- Les zones cliquables font au moins 44 px, et 48 px pour les lignes sur mobile.
- Le focus est toujours visible (`:focus-visible`).

---

## 7. Feuille de route

### Historique

- **0.1.2** : rendu sur Divi. Le panneau s'ancre sur la section du header (pleine largeur, collé sous la barre, contenu aligné sur elle). Liens forcés en `!important` contre le `a { color … !important }` du thème enfant. Grille de groupes adaptative, un groupe seul passe sur 2 colonnes. Colonne de droite masquée quand elle est vide.
- **0.1.1** : le panneau passait sous d'autres éléments du header Divi (ligne en `z-index: 4`). Les parents du menu sont maintenant remontés au-dessus du reste (`:has()` en CSS et classe `.tmm-lift` posée en JS). Menus de test « accès directs » et « recherches fréquentes » créés sur tivoli.digima.be.
- **0.1.0** : prototype maître-détail.

### v0.2 : mise en service sur staging

- [ ] Tester sur un WordPress avec Divi : rendu, survol, clavier, mobile, header fixe
- [ ] Corriger les conflits de CSS avec Divi (marges des `ul`/`p`, styles des `button`)
- [ ] Créer le script de données de test (WP-CLI)
- [ ] Vérifier avec un lecteur d'écran (NVDA, VoiceOver)
- [ ] Faire un audit axe ou Lighthouse sur les panneaux ouverts

### v0.3 : finitions

- [ ] Appliquer la piste A aux autres menus (Patients et proches, CHU Tivoli, Espace pro), une fois leur contenu récupéré
- [ ] Afficher les champs « Mega menu » dans le Customizer (facultatif)
- [ ] Rendre les panneaux de niveau 1 sans enfants : dropdown simple, ou lien direct (comportement actuel)
- [ ] Mettre en cache l'arbre du menu, et non le HTML qui dépend de la page en cours
- [ ] Internationaliser les libellés codés en dur (« Accès directs », « Vous ne trouvez pas ? »…)
- [ ] Ajouter un `uninstall.php` qui supprime les post meta `_tmm_*`

### À arbitrer avec le client

- Le 3e niveau : il est techniquement possible, mais on le déconseille. Chaque niveau de plus coûte un clic sur mobile.
- Le contenu des encarts pour chaque rubrique.
- Les regroupements Avant / Pendant / Après : ce sont des propositions, à valider.

---

## 8. Conventions de code

- PHP aux **WordPress Coding Standards** : préfixe `tmm_` sur les fonctions, échappement en sortie (`esc_html`, `esc_attr`, `esc_url`), assainissement en entrée.
- JS en vanilla ES5, sans dépendance et sans build. Le support des anciens navigateurs passe par `matchMedia.addListener`.
- CSS : toutes les classes sont préfixées `.tmm-`. Pas de `!important`, sauf pour neutraliser les styles de Divi (et à commenter).
- Commentaires et libellés en français.

---

## 9. Checklist de recette

- [ ] Desktop 1440 / 1280 / 1024 px : le panneau s'ouvre au survol et au clic, et se referme hors survol, sur Échap et en cas de clic ailleurs
- [ ] Le changement de rubrique au survol ne saute pas quand la souris se déplace en diagonale
- [ ] Le parcours complet au clavier fonctionne, sans piège de focus
- [ ] Mobile 390 px : Menu › Offre de soins › Hospitalisation › page, avec les boutons retour à chaque niveau
- [ ] Sur une page enfant, l'entrée de la barre est mise en évidence et le lien porte `aria-current`
- [ ] L'encart rouge Urgences et le lien 112 sont visibles
- [ ] La recherche renvoie vers `/?s=`
- [ ] Pas d'erreur PHP ni d'erreur dans la console JS
