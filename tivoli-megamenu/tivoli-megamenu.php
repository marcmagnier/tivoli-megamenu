<?php
/**
 * Plugin Name:       Tivoli Mega Menu
 * Description:       Mega menu « maître-détail », barre supérieure et en-tête complet, pilotés depuis Apparence › Menus. Shortcodes [tivoli_header] (tout l’en-tête), [tivoli_megamenu] et [tivoli_topbar].
 * Version:           0.3.1
 * Requires at least: 5.4
 * Requires PHP:      7.4
 * Text Domain:       tivoli-megamenu
 */

defined( 'ABSPATH' ) || exit;

define( 'TMM_VERSION', '0.3.1' );
define( 'TMM_URL', plugin_dir_url( __FILE__ ) );

/* -------------------------------------------------------------------------
 * 1. Emplacements de menu (Apparence › Menus › Gérer les emplacements)
 * ---------------------------------------------------------------------- */
add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'tmm_main'    => __( 'Mega menu – navigation principale', 'tivoli-megamenu' ),
		'tmm_quick'   => __( 'Mega menu – accès directs (colonne de droite)', 'tivoli-megamenu' ),
		'tmm_popular' => __( 'Mega menu – recherches fréquentes', 'tivoli-megamenu' ),
		'tmm_topbar'  => __( 'Barre supérieure', 'tivoli-megamenu' ),
	) );
}, 20 );

/* -------------------------------------------------------------------------
 * 2. Champs supplémentaires sur les éléments de menu
 * ---------------------------------------------------------------------- */
function tmm_fields() {
	return array(
		'group'       => array( 'type' => 'checkbox', 'label' => 'Titre de groupe (niveau 3) – ses enfants sont listés sous ce titre' ),
		'feat_kicker' => array( 'type' => 'text',     'label' => 'Encart – surtitre (niveau 2 / rubrique)' ),
		'feat_title'  => array( 'type' => 'text',     'label' => 'Encart – titre' ),
		'feat_text'   => array( 'type' => 'textarea', 'label' => 'Encart – texte' ),
		'feat_cta'    => array( 'type' => 'text',     'label' => 'Encart – libellé du bouton' ),
		'feat_url'    => array( 'type' => 'url',      'label' => 'Encart – lien du bouton' ),
		'feat_tone'   => array( 'type' => 'select',   'label' => 'Encart – couleur', 'options' => array( 'teal' => 'Pétrole (standard)', 'red' => 'Rouge (urgence)' ) ),
	);
}

function tmm_meta( $item_id, $key ) {
	return get_post_meta( $item_id, '_tmm_' . $key, true );
}

add_action( 'wp_nav_menu_item_custom_fields', function ( $item_id ) {
	echo '<details class="tmm-admin description-wide" style="margin:8px 0;padding:8px 10px;border:1px solid #dcdcde;background:#f6f7f7">';
	echo '<summary style="cursor:pointer;font-weight:600">Mega menu</summary>';
	wp_nonce_field( 'tmm_save_' . $item_id, 'tmm_nonce_' . $item_id );
	foreach ( tmm_fields() as $key => $f ) {
		$name  = 'tmm[' . (int) $item_id . '][' . $key . ']';
		$id    = 'tmm-' . $key . '-' . (int) $item_id;
		$value = tmm_meta( $item_id, $key );
		echo '<p style="margin:8px 0">';
		if ( 'checkbox' === $f['type'] ) {
			printf( '<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
				esc_attr( $id ), esc_attr( $name ), checked( $value, '1', false ), esc_html( $f['label'] ) );
		} else {
			printf( '<label for="%s">%s</label><br>', esc_attr( $id ), esc_html( $f['label'] ) );
			if ( 'textarea' === $f['type'] ) {
				printf( '<textarea id="%s" name="%s" rows="2" class="widefat">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			} elseif ( 'select' === $f['type'] ) {
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $f['options'] as $k => $l ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $value, $k, false ), esc_html( $l ) );
				}
				echo '</select>';
			} else {
				printf( '<input type="%s" id="%s" name="%s" value="%s" class="widefat">',
					esc_attr( 'url' === $f['type'] ? 'url' : 'text' ), esc_attr( $id ), esc_attr( $name ),
					esc_attr( 'url' === $f['type'] ? esc_url( $value ) : $value ) );
			}
		}
		echo '</p>';
	}
	echo '<p class="description">La description native (Options de l’écran › Description) sert de sous-titre à la rubrique.</p>';
	echo '</details>';
} );

add_action( 'wp_update_nav_menu_item', function ( $menu_id, $item_id ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$nonce = isset( $_POST[ 'tmm_nonce_' . $item_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'tmm_nonce_' . $item_id ] ) ) : '';
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'tmm_save_' . $item_id ) ) {
		return; // Pas envoyé depuis l'écran Menus (ex. Customizer) : on ne touche à rien.
	}
	$data = isset( $_POST['tmm'][ $item_id ] ) ? (array) wp_unslash( $_POST['tmm'][ $item_id ] ) : array();
	foreach ( tmm_fields() as $key => $f ) {
		$raw = isset( $data[ $key ] ) ? $data[ $key ] : '';
		switch ( $f['type'] ) {
			case 'checkbox': $val = $raw ? '1' : ''; break;
			case 'textarea': $val = sanitize_textarea_field( $raw ); break;
			case 'url':      $val = esc_url_raw( $raw ); break;
			case 'select':   $val = array_key_exists( $raw, $f['options'] ) ? $raw : ''; break;
			default:         $val = sanitize_text_field( $raw );
		}
		if ( '' === $val ) {
			delete_post_meta( $item_id, '_tmm_' . $key );
		} else {
			update_post_meta( $item_id, '_tmm_' . $key, $val );
		}
	}
}, 10, 2 );

/* -------------------------------------------------------------------------
 * 3. Construction de l'arbre du menu
 * ---------------------------------------------------------------------- */
function tmm_tree( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ], array( 'update_post_term_cache' => false ) );
	if ( empty( $items ) ) {
		return array();
	}
	_wp_menu_item_classes_by_context( $items ); // positionne ->current, ->current_item_ancestor…
	$by_id = array();
	foreach ( $items as $it ) {
		$it->tmm_children     = array();
		$by_id[ $it->ID ] = $it;
	}
	$root = array();
	foreach ( $items as $it ) {
		$parent = (int) $it->menu_item_parent;
		if ( $parent && isset( $by_id[ $parent ] ) ) {
			$by_id[ $parent ]->tmm_children[] = $it;
		} else {
			$root[] = $it;
		}
	}
	return $root;
}

function tmm_url( $it ) {
	$url = trim( (string) $it->url );
	return ( '' === $url || '#' === $url ) ? '' : $url;
}

/**
 * Icône associée à l'élément par le plugin « Menu Icons » (Codeinwp), s'il est actif.
 * Le plugin ne l'ajoute qu'aux menus rendus par wp_nav_menu() : on l'appelle donc nous-mêmes.
 * Ses feuilles de style (polices d'icônes) sont déjà chargées sur toutes les pages.
 */
function tmm_icon( $it ) {
	if ( ! class_exists( 'Menu_Icons_Meta' ) || ! class_exists( 'Menu_Icons_Front_End' ) ) {
		return '';
	}
	$icon = Menu_Icons_Front_End::get_icon( Menu_Icons_Meta::get( $it->ID ) );
	return $icon ? '<span class="tmm-ico" aria-hidden="true">' . $icon . '</span>' : '';
}

function tmm_link( $it, $class = '' ) {
	$url  = tmm_url( $it );
	$icon = tmm_icon( $it );
	if ( $icon ) {
		$class = trim( $class . ' has-ico' );
	}
	if ( ! $url ) {
		return '<span class="' . esc_attr( $class ) . '">' . $icon . esc_html( $it->title ) . '</span>';
	}
	$attrs = ' href="' . esc_url( $url ) . '"';
	if ( $class ) {
		$attrs .= ' class="' . esc_attr( $class ) . '"';
	}
	if ( ! empty( $it->current ) ) {
		$attrs .= ' aria-current="page"';
	}
	if ( ! empty( $it->target ) ) {
		$attrs .= ' target="' . esc_attr( $it->target ) . '" rel="noopener' . ( $it->xfn ? ' ' . esc_attr( $it->xfn ) : '' ) . '"';
	} elseif ( ! empty( $it->xfn ) ) {
		$attrs .= ' rel="' . esc_attr( $it->xfn ) . '"';
	}
	$label = $icon ? $icon . '<span class="tmm-label">' . esc_html( $it->title ) . '</span>' : esc_html( $it->title );
	return '<a' . $attrs . '>' . $label . '</a>';
}

/** Regroupe les enfants d'une rubrique : éléments « Titre de groupe » + éléments libres consécutifs. */
function tmm_groups( $rubrique ) {
	$groups = array();
	$loose  = array();
	foreach ( $rubrique->tmm_children as $child ) {
		if ( tmm_meta( $child->ID, 'group' ) ) {
			if ( $loose ) {
				$groups[] = array( 'head' => null, 'items' => $loose );
				$loose    = array();
			}
			$groups[] = array( 'head' => $child, 'items' => $child->tmm_children );
		} else {
			$loose[] = $child;
		}
	}
	if ( $loose ) {
		$groups[] = array( 'head' => null, 'items' => $loose );
	}
	return $groups;
}

/* -------------------------------------------------------------------------
 * 4. Rendu (shortcode)
 * ---------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'tmm', TMM_URL . 'assets/megamenu.css', array(), TMM_VERSION );
	wp_register_script( 'tmm', TMM_URL . 'assets/megamenu.js', array(), TMM_VERSION, true );
} );

add_shortcode( 'tivoli_megamenu', 'tmm_render' );

function tmm_render() {
	return tmm_render_nav();
}

/**
 * @param array $extra  'logo' : <li> placé en tête de l'arbre mobile ;
 *                      'util' : <li> ajoutés après les entrées principales (mobile) ;
 *                      'tail' : <li> ajoutés tout en bas de l'arbre (mobile).
 */
function tmm_render_nav( $extra = array() ) {
	$extra = wp_parse_args( $extra, array( 'logo' => '', 'util' => '', 'tail' => '' ) );
	$tree = tmm_tree( 'tmm_main' );
	if ( ! $tree ) {
		return current_user_can( 'edit_theme_options' )
			? '<p><strong>Tivoli Mega Menu :</strong> assignez un menu à l’emplacement « Mega menu – navigation principale » (Apparence › Menus).</p>'
			: '';
	}
	wp_enqueue_style( 'tmm' );
	wp_enqueue_script( 'tmm' );

	$aside_extra = tmm_quick_links();

	ob_start();
	?>
	<nav class="tmm" aria-label="<?php esc_attr_e( 'Navigation principale', 'tivoli-megamenu' ); ?>" data-tmm>
		<button type="button" class="tmm-burger" aria-expanded="false" aria-controls="tmm-bar">
			<span class="tmm-burger__icon" aria-hidden="true"></span><span class="tmm-burger__label">Menu</span>
		</button>
		<ul class="tmm-bar" id="tmm-bar">
			<?php echo $extra['logo']; // phpcs:ignore -- HTML construit par le plugin ?>
			<?php foreach ( $tree as $top ) :
				$is_current = ! empty( $top->current ) || ! empty( $top->current_item_ancestor );
				$classes    = 'tmm-top' . ( $is_current ? ' is-current' : '' ) . ( $top->tmm_children ? ' has-panel' : '' );
				?>
				<li class="<?php echo esc_attr( $classes ); ?>">
				<?php if ( ! $top->tmm_children ) : ?>
					<?php echo tmm_link( $top, 'tmm-top__link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else :
					$pid = 'tmm-p-' . (int) $top->ID;
					?>
					<button type="button" class="tmm-top__btn" aria-expanded="false" aria-controls="<?php echo esc_attr( $pid ); ?>">
						<?php echo esc_html( $top->title ); ?>
						<svg class="tmm-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
					</button>
					<div class="tmm-panel" id="<?php echo esc_attr( $pid ); ?>" hidden>
						<div class="tmm-panel__inner">
							<div class="tmm-rail">
								<p class="tmm-eyebrow"><?php echo esc_html( $top->title ); ?></p>
								<ul class="tmm-rail__list">
								<?php foreach ( $top->tmm_children as $i => $rub ) : ?>
									<li>
										<button type="button" class="tmm-rail__btn" data-tmm-rail aria-expanded="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-controls="tmm-d-<?php echo (int) $rub->ID; ?>">
											<span><?php echo esc_html( $rub->title ); ?></span>
											<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
										</button>
									</li>
								<?php endforeach; ?>
								</ul>
								<?php if ( tmm_url( $top ) ) : ?>
									<a class="tmm-rail__all" href="<?php echo esc_url( tmm_url( $top ) ); ?>"><?php echo esc_html( sprintf( 'Toute la rubrique %s →', $top->title ) ); ?></a>
								<?php endif; ?>
							</div>

							<?php foreach ( $top->tmm_children as $i => $rub ) : ?>
							<?php $aside = trim( tmm_feature( $rub ) . $aside_extra ); ?>
							<div class="tmm-detail<?php echo $aside ? '' : ' tmm-detail--full'; ?>" id="tmm-d-<?php echo (int) $rub->ID; ?>" <?php echo 0 === $i ? '' : 'hidden'; ?>>
								<div class="tmm-detail__main">
									<div class="tmm-detail__head">
										<p class="tmm-detail__title"><?php echo esc_html( $rub->title ); ?></p>
										<?php if ( tmm_url( $rub ) ) : ?>
											<a class="tmm-detail__more" href="<?php echo esc_url( tmm_url( $rub ) ); ?>">Page d’accueil de la rubrique →</a>
										<?php endif; ?>
									</div>
									<?php if ( trim( (string) $rub->description ) ) : ?>
										<p class="tmm-detail__desc"><?php echo esc_html( $rub->description ); ?></p>
									<?php endif; ?>
									<div class="tmm-groups">
									<?php foreach ( tmm_groups( $rub ) as $g ) : ?>
										<div class="tmm-group">
											<?php if ( $g['head'] ) : ?>
												<p class="tmm-group__title"><?php echo tmm_link( $g['head'] ); // phpcs:ignore ?></p>
											<?php endif; ?>
											<ul class="tmm-group__list">
											<?php foreach ( $g['items'] as $it ) : ?>
												<li>
													<?php echo tmm_link( $it, 'tmm-link' ); // phpcs:ignore ?>
													<?php if ( $it->tmm_children ) : ?>
														<ul class="tmm-kids">
														<?php foreach ( $it->tmm_children as $kid ) : ?>
															<li><?php echo tmm_link( $kid ); // phpcs:ignore ?></li>
														<?php endforeach; ?>
														</ul>
													<?php endif; ?>
												</li>
											<?php endforeach; ?>
											</ul>
										</div>
									<?php endforeach; ?>
									</div>
								</div>
								<?php if ( $aside ) : ?>
								<div class="tmm-detail__aside"><?php echo $aside; // phpcs:ignore -- HTML déjà échappé ?></div>
								<?php endif; ?>
							</div>
							<?php endforeach; ?>
						</div>
						<?php echo tmm_footer(); // phpcs:ignore ?>
					</div>
				<?php endif; ?>
				</li>
			<?php endforeach; ?>
			<?php echo $extra['util']; // phpcs:ignore -- HTML construit par le plugin ?>
			<?php // Mobile uniquement : accès directs et recherche, une seule fois en bas de l'arbre. ?>
			<li class="tmm-mobile-extra"><?php echo $aside_extra . tmm_footer(); // phpcs:ignore -- HTML déjà échappé ?></li>
			<?php echo $extra['tail']; // phpcs:ignore -- HTML construit par le plugin ?>
		</ul>
	</nav>
	<?php
	return ob_get_clean();
}

function tmm_feature( $rub ) {
	$title = tmm_meta( $rub->ID, 'feat_title' );
	if ( ! $title ) {
		return '';
	}
	$tone = 'red' === tmm_meta( $rub->ID, 'feat_tone' ) ? 'red' : 'teal';
	$html = '<div class="tmm-feat tmm-feat--' . $tone . '">';
	if ( $k = tmm_meta( $rub->ID, 'feat_kicker' ) ) {
		$html .= '<p class="tmm-feat__kicker">' . esc_html( $k ) . '</p>';
	}
	$html .= '<p class="tmm-feat__title">' . esc_html( $title ) . '</p>';
	if ( $t = tmm_meta( $rub->ID, 'feat_text' ) ) {
		$html .= '<p class="tmm-feat__text">' . esc_html( $t ) . '</p>';
	}
	$cta = tmm_meta( $rub->ID, 'feat_cta' );
	$url = tmm_meta( $rub->ID, 'feat_url' );
	if ( $cta && $url ) {
		$html .= '<a class="tmm-feat__cta" href="' . esc_url( $url ) . '">' . esc_html( $cta ) . '</a>';
	}
	return $html . '</div>';
}

function tmm_quick_links() {
	$items = tmm_tree( 'tmm_quick' );
	if ( ! $items ) {
		return '';
	}
	$html = '<div class="tmm-quick-wrap"><p class="tmm-eyebrow">Accès directs</p><ul class="tmm-quick">';
	foreach ( $items as $it ) {
		$html .= '<li>' . tmm_link( $it, 'tmm-link' ) . '</li>';
	}
	return $html . '</ul></div>';
}

function tmm_footer() {
	static $n = 0;
	$n++;
	$id      = 'tmm-s-' . $n;
	$popular = tmm_tree( 'tmm_popular' );
	// Page de résultats personnalisée (Ajax Search Pro) : /resultat-de-la-recherche/?asp=…
	// Modifiable via les filtres tmm_search_action et tmm_search_param.
	$action  = apply_filters( 'tmm_search_action', home_url( '/resultat-de-la-recherche/' ) );
	$param   = apply_filters( 'tmm_search_param', 'asp' );
	$html    = '<div class="tmm-foot"><div class="tmm-foot__inner">';
	$html   .= '<form class="tmm-search" role="search" method="get" action="' . esc_url( $action ) . '">';
	$html   .= '<label for="' . esc_attr( $id ) . '">Vous ne trouvez pas ?</label>';
	$html   .= '<span class="tmm-search__field"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>';
	$html   .= '<input type="search" id="' . esc_attr( $id ) . '" name="' . esc_attr( $param ) . '" placeholder="Un service, un médecin, une pathologie…"></span>';
	$html   .= '<button type="submit" class="tmm-search__btn">Rechercher</button></form>';
	if ( $popular ) {
		$html .= '<div class="tmm-popular"><span>Recherches fréquentes :</span>';
		foreach ( $popular as $it ) {
			$html .= tmm_link( $it, 'tmm-chip' );
		}
		$html .= '</div>';
	}
	return $html . '</div></div>';
}

/* -------------------------------------------------------------------------
 * 5. Barre supérieure (shortcode [tivoli_topbar])
 *
 * Menu à plat assigné à l'emplacement « Barre supérieure ».
 * Icônes : plugin Menu Icons. Style d'un élément : champ natif « Classes CSS »
 * (Apparence › Menus › Options de l'écran) :
 *   tmm-btn-red   bouton rouge (ex. « 50 ans du CHU Tivoli »)
 *   tmm-btn-teal  bouton pétrole (ex. « Prendre rendez-vous »)
 * L'option « Masquer le libellé » de Menu Icons donne une icône seule.
 *
 * Réseaux sociaux : ajoutés par le plugin en fin de barre (icônes SVG colorées).
 * Liens modifiables dans le shortcode, un attribut vide masque le réseau :
 *   [tivoli_topbar facebook="…" linkedin="…" youtube="…" instagram="…"]
 * Les éléments du menu qui pointent vers ces réseaux sont ignorés (pas de doublon).
 * ---------------------------------------------------------------------- */
add_shortcode( 'tivoli_topbar', 'tmm_topbar_render' );

/** Réseaux par défaut : libellé, couleur, viewBox, tracé (Font Awesome Free 5, CC BY 4.0). */
function tmm_social_networks() {
	return array(
		'facebook'  => array( 'Facebook', '#1877F2', '0 0 448 512', 'M400 32H48A48 48 0 0 0 0 80v352a48 48 0 0 0 48 48h137.25V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.27c-30.81 0-40.42 19.12-40.42 38.73V256h68.78l-11 71.69h-57.78V480H400a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48z' ),
		'linkedin'  => array( 'LinkedIn', '#0A66C2', '0 0 448 512', 'M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z' ),
		'youtube'   => array( 'YouTube', '#FF0000', '0 0 576 512', 'M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z' ),
		'instagram' => array( 'Instagram', 'url(#tmm-ig)', '0 0 448 512', 'M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z' ),
	);
}

/** Liens des réseaux : valeurs par défaut (filtre tmm_social_links) + attributs du shortcode. */
function tmm_social_atts( $atts, $extra_defaults = array(), $tag = 'tivoli_topbar' ) {
	$defaults = apply_filters(
		'tmm_social_links',
		array(
			'facebook'  => 'https://www.facebook.com/chutivoli',
			'linkedin'  => 'https://www.linkedin.com/company/chu-tivoli',
			'youtube'   => 'https://www.youtube.com/@chutivoli8915',
			'instagram' => 'https://www.instagram.com/chutivoli',
		)
	);
	return shortcode_atts( array_merge( $defaults, $extra_defaults ), (array) $atts, $tag );
}

/** Liens des réseaux (<a> avec SVG coloré). Un identifiant de dégradé unique par appel. */
function tmm_social_links( $atts, $a_class ) {
	static $n = 0;
	$n++;
	$out = array();
	foreach ( tmm_social_networks() as $key => $net ) {
		$url = trim( (string) $atts[ $key ] );
		if ( '' === $url ) {
			continue;
		}
		$fill = $net[1];
		$defs = '';
		if ( 'instagram' === $key ) {
			$gid  = 'tmm-ig-' . $n;
			$fill = 'url(#' . $gid . ')';
			$defs = '<defs><linearGradient id="' . $gid . '" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#FEDA75"/><stop offset=".35" stop-color="#FA7E1E"/><stop offset=".6" stop-color="#D62976"/><stop offset=".85" stop-color="#962FBF"/><stop offset="1" stop-color="#4F5BD5"/></linearGradient></defs>';
		}
		$out[ $key ] = '<a class="' . esc_attr( $a_class ) . ' tmm-social--' . esc_attr( $key ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">'
			. '<svg class="tmm-social__svg" viewBox="' . esc_attr( $net[2] ) . '" aria-hidden="true" focusable="false">' . $defs
			. '<path fill="' . esc_attr( $fill ) . '" d="' . esc_attr( $net[3] ) . '"/></svg>'
			. '<span class="tmm-sr">' . esc_html( $net[0] ) . '</span></a>';
	}
	return $out;
}

/** Éléments du menu « Barre supérieure », sans ceux qui pointent vers un réseau social (gérés par le plugin). */
function tmm_topbar_items() {
	$items = array();
	foreach ( tmm_tree( 'tmm_topbar' ) as $it ) {
		if ( in_array( 'tmm-social', (array) $it->classes, true ) || preg_match( '/facebook|linkedin|youtube|instagram/i', (string) $it->url ) ) {
			continue;
		}
		$items[] = $it;
	}
	return $items;
}

function tmm_topbar_html( $atts ) {
	$items = tmm_topbar_items();
	if ( ! $items ) {
		return current_user_can( 'edit_theme_options' )
			? '<p><strong>Tivoli Mega Menu :</strong> assignez un menu à l’emplacement « Barre supérieure » (Apparence › Menus).</p>'
			: '';
	}
	wp_enqueue_style( 'tmm' );

	$allowed = array( 'tmm-btn-red', 'tmm-btn-teal' );
	$html    = '<nav class="tmm-topbar" aria-label="' . esc_attr__( 'Liens utiles', 'tivoli-megamenu' ) . '"><ul class="tmm-topbar__list">';
	foreach ( $items as $it ) {
		$classes = array_intersect( (array) $it->classes, $allowed );
		if ( class_exists( 'Menu_Icons_Meta' ) ) {
			$meta = Menu_Icons_Meta::get( $it->ID );
			if ( ! empty( $meta['hide_label'] ) ) {
				$classes[] = 'is-icon-only';
			}
		}
		$html .= '<li class="' . esc_attr( implode( ' ', array_unique( array_merge( array( 'tmm-topbar__item' ), $classes ) ) ) ) . '">';
		$html .= tmm_link( $it, 'tmm-topbar__link' );
		$html .= '</li>';
	}
	foreach ( tmm_social_links( $atts, 'tmm-topbar__link' ) as $key => $a ) {
		$html .= '<li class="tmm-topbar__item tmm-social">' . $a . '</li>';
	}
	return $html . '</ul></nav>';
}

function tmm_topbar_render( $atts = array() ) {
	return tmm_topbar_html( tmm_social_atts( $atts ) );
}

/* -------------------------------------------------------------------------
 * 6. En-tête complet (shortcode [tivoli_header])
 *
 * Un seul shortcode pour tout le header : barre supérieure + logo + mega menu.
 *   Desktop : 1re ligne = barre supérieure (alignée à droite) ;
 *             2e ligne = logo à gauche, mega menu à droite.
 *   Mobile (≤ 980 px) : « CHU Tivoli » en texte discret + bouton Menu.
 *             Dans l'arbre : le logo en tête, puis les entrées principales,
 *             les liens de la barre supérieure (sans boutons colorés),
 *             accès directs et recherche, et les 4 réseaux sur toute la largeur.
 *
 *   [tivoli_header logo="…" name="CHU Tivoli" facebook="…" linkedin="…" youtube="…" instagram="…"]
 * ---------------------------------------------------------------------- */
add_shortcode( 'tivoli_header', 'tmm_header_render' );

function tmm_header_render( $atts = array() ) {
	$atts = tmm_social_atts(
		$atts,
		array(
			'logo' => '/wp-content/uploads/2026/01/logo-tivoli-acc.png',
			'name' => 'CHU Tivoli',
		),
		'tivoli_header'
	);
	$home = home_url( '/' );
	$logo = trim( (string) $atts['logo'] );
	if ( $logo && '/' === $logo[0] ) {
		$logo = home_url( $logo );
	}
	$img = $logo
		? '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $atts['name'] . ' – accueil' ) . '" width="668" height="180">'
		: esc_html( $atts['name'] );

	// Mobile : liens de la barre supérieure, à la suite des entrées principales.
	$util = '';
	foreach ( tmm_topbar_items() as $it ) {
		$util .= '<li class="tmm-top tmm-m-only tmm-m-util">' . tmm_link( $it, 'tmm-top__link' ) . '</li>';
	}
	$social = tmm_social_links( $atts, 'tmm-m-social__link' );

	$nav = tmm_render_nav(
		array(
			'logo' => $logo ? '<li class="tmm-m-only tmm-m-logo"><a href="' . esc_url( $home ) . '">' . $img . '</a></li>' : '',
			'util' => $util,
			'tail' => $social ? '<li class="tmm-m-only tmm-m-social">' . implode( '', $social ) . '</li>' : '',
		)
	);
	wp_enqueue_style( 'tmm' );

	return '<div class="tmm-header">'
		. '<div class="tmm-header__top">' . tmm_topbar_html( $atts ) . '</div>'
		. '<div class="tmm-header__main">'
		. '<a class="tmm-header__logo" href="' . esc_url( $home ) . '">' . $img . '</a>'
		. '<a class="tmm-header__name" href="' . esc_url( $home ) . '">' . esc_html( $atts['name'] ) . '</a>'
		. $nav
		. '</div></div>';
}
