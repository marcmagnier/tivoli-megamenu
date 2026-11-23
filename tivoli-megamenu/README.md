# Tivoli Mega Menu

Mega menu « maître-détail » géré depuis **Apparence › Menus** et affiché avec le shortcode `[tivoli_megamenu]`.

## Installation

1. Extensions › Ajouter › Téléverser le fichier `tivoli-megamenu.zip`, puis activer l'extension.
2. Apparence › Menus › **Gérer les emplacements** : assigner
   - **Mega menu – navigation principale** : le menu principal (obligatoire)
   - **Mega menu – accès directs** : 3 ou 4 liens (Trouver un médecin, Plan d'accès…), facultatif
   - **Mega menu – recherches fréquentes** : 3 ou 4 liens « chips », facultatif
3. Divi › Theme Builder › en-tête global : remplacer le module **Menu** par un module **Code** contenant `[tivoli_megamenu]`.
   - La section qui contient le header doit rester en `position: relative` (c'est le défaut chez Divi). Le panneau prend toute sa largeur.
   - Dans la section et la ligne : Avancé › Visibilité › Débordement = **Visible**, et un z-index supérieur à celui du contenu.
   - Mobile : régler `--tmm-offset` sur la hauteur réelle du header (voir Personnalisation).

## Structure du menu

| Profondeur dans Apparence › Menus | Rôle | Exemple |
|---|---|---|
| Niveau 1 | Entrée de la barre. Si l'élément a des enfants, il ouvre un panneau. | Offre de soins |
| Niveau 2 | Rubrique de la colonne de gauche | Hospitalisation |
| Niveau 3 | Page, **ou** titre de groupe si la case « Titre de groupe » est cochée | Avant le séjour |
| Niveau 4 | Pages d'un groupe, ou 3e niveau sous une page | Que faut-il emporter ? |
| Niveau 5 | 3e niveau sous une page d'un groupe (petits liens indentés) | [Unité 1] |

- Un titre de groupe peut être un lien personnalisé avec l'URL `#` : il s'affiche alors comme un simple intertitre, non cliquable.
- **Description** native de la rubrique (à activer dans Options de l'écran › Description) : la phrase sous le titre.
- **Encart** de la colonne de droite (surtitre, titre, texte, bouton, couleur) : champs « Mega menu » sur l'élément de niveau 2. Si le titre est vide, l'encart n'apparaît pas.
- Si l'URL d'un élément de niveau 1 ou 2 n'est pas `#`, le panneau affiche un lien vers sa page d'accueil.

## Personnalisation

À ajouter dans Divi › Options du thème › CSS personnalisé :

```css
.tmm {
  --tmm-red: #C8161D;
  --tmm-teal: #1E4E5A;
  --tmm-max: 1280px;   /* largeur du contenu du panneau */
  --tmm-offset: 80px;  /* mobile : hauteur du header */
}
```

## Accessibilité (intégrée)

- Les entrées de niveau 1 et les rubriques sont de vrais `<button>` avec `aria-expanded` et `aria-controls`.
- Échap referme le panneau et rend le focus au déclencheur. Le menu se referme aussi quand le focus en sort.
- Les flèches ↑ et ↓ parcourent les rubriques ; → entre dans le détail.
- `aria-current="page"` est posé sur la page en cours.
- Le survol s'ouvre avec un petit délai, pour éviter qu'une rubrique change quand la souris traverse la liste en diagonale.

## Limites de la v0.1

- Les champs personnalisés n'apparaissent pas dans le Customizer, seulement dans l'écran Apparence › Menus.
- Pas de mise en cache du HTML : le menu dépend de la page en cours (élément actif). Si besoin, mettre en cache uniquement l'arbre du menu.
- Un seul shortcode par page.
