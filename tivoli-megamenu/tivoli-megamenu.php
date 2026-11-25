<?php
/**
 * Plugin Name:       Tivoli Mega Menu
 * Description:       Mega menu « maître-détail » piloté depuis Apparence › Menus. Affichage via le shortcode [tivoli_megamenu] (ex. dans le header du Divi Theme Builder).
 * Version:           0.1.5
 * Requires at least: 5.4
 * Requires PHP:      7.4
 * Text Domain:       tivoli-megamenu
 */

defined( 'ABSPATH' ) || exit;

define( 'TMM_VERSION', '0.1.5' );
define( 'TMM_URL', plugin_dir_url( __FILE__ ) );

/* -------------------------------------------------------------------------
 * 1. Emplacements de menu (Apparence › Menus › Gérer les emplacements)
 * ---------------------------------------------------------------------- */
add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'tmm_main'    => __( 'Mega menu – navigation principale', 'tivoli-megamenu' ),
		'tmm_quick'   => __( 'Mega menu – accès directs (colonne de droite)', 'tivoli-megamenu' ),
		'tmm_popular' => __( 'Mega menu – recherches fréquentes', 'tivoli-megamenu' ),
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
			<?php // Mobile uniquement : accès directs et recherche, une seule fois en bas de l'arbre. ?>
			<li class="tmm-mobile-extra"><?php echo $aside_extra . tmm_footer(); // phpcs:ignore -- HTML déjà échappé ?></li>
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
