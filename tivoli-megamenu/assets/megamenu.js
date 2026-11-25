/* Tivoli Mega Menu – comportement (sans dépendance)
   Desktop : panneau maître-détail (rubriques à gauche, détail à droite).
   Mobile (≤ 980 px) : arbre dépliable simple. Chaque niveau s'ouvre sous son bouton. */
(function () {
	'use strict';

	var mq = window.matchMedia('(max-width: 980px)');
	var isMobile = function () { return mq.matches; };

	function byId(id) { return document.getElementById(id); }
	function each(list, fn) { Array.prototype.forEach.call(list, fn); }

	// Dans Divi, le panneau s'ancre sur la section (pleine largeur) : les éléments entre
	// le menu et la section repassent en statique, la section et ses parents sont remontés.
	function lift(nav) {
		var host = nav.closest ? nav.closest('.et_pb_section') : null;
		var el = nav.parentElement;
		while (el && el !== document.body) {
			if (host && el !== host && host.contains(el)) {
				el.classList.add('tmm-static');
			} else {
				var cs = window.getComputedStyle(el);
				if (cs.position !== 'static' || cs.zIndex !== 'auto') el.classList.add('tmm-lift');
			}
			if (el.matches('.et-l--header, header, #main-header')) break;
			el = el.parentElement;
		}
		return host;
	}

	function init(nav) {
		var host = lift(nav);
		var bar = nav.querySelector('.tmm-bar');
		var header = nav.closest ? nav.closest('.tmm-header') : null;
		var tops = Array.prototype.slice.call(nav.querySelectorAll('.tmm-top__btn'));
		var burger = nav.querySelector('.tmm-burger');
		var panels = Array.prototype.slice.call(nav.querySelectorAll('.tmm-panel'));
		var openBtn = null;
		var openedByHoverAt = 0;
		var tOpen, tClose;

		function panelOf(btn) { return byId(btn.getAttribute('aria-controls')); }
		function railsOf(panel) { return Array.prototype.slice.call(panel.querySelectorAll('[data-tmm-rail]')); }

		// Desktop : colle le panneau sous la barre et aligne son contenu sur elle.
		function place(panel) {
			if (isMobile()) {
				panel.style.top = '';
				panel.style.removeProperty('--tmm-max');
				return;
			}
			var anchor = panel.offsetParent;
			if (!anchor) return;
			var a = anchor.getBoundingClientRect();
			var b = bar.getBoundingClientRect();
			panel.style.top = Math.round(b.bottom - a.top) + 'px';
			// Avec [tivoli_header], le contenu du panneau s'aligne sur tout l'en-tête (logo compris).
			var ref = header ? header.getBoundingClientRect() : b;
			if (host) panel.style.setProperty('--tmm-max', Math.round(ref.width + 64) + 'px');
		}

		/* Mise en page selon le mode.
		   Mobile : chaque détail est déplacé sous le bouton de sa rubrique, tout est replié.
		   Desktop : les détails retournent à droite de la colonne, la 1re rubrique est active. */
		function layout() {
			var mobile = isMobile();
			panels.forEach(function (panel) {
				var inner = panel.querySelector('.tmm-panel__inner');
				railsOf(panel).forEach(function (rail, i) {
					var detail = byId(rail.getAttribute('aria-controls'));
					if (mobile) rail.parentElement.appendChild(detail);
					else inner.appendChild(detail);
					var on = !mobile && i === 0;
					rail.setAttribute('aria-expanded', on ? 'true' : 'false');
					detail.hidden = !on;
				});
			});
		}

		function open(btn, viaHover) {
			if (openBtn && openBtn !== btn) close(openBtn, false);
			btn.setAttribute('aria-expanded', 'true');
			panelOf(btn).hidden = false;
			place(panelOf(btn));
			openBtn = btn;
			nav.classList.add('is-open');
			if (viaHover) openedByHoverAt = Date.now();
		}

		function close(btn, restoreFocus) {
			btn.setAttribute('aria-expanded', 'false');
			panelOf(btn).hidden = true;
			if (openBtn === btn) openBtn = null;
			if (!openBtn) nav.classList.remove('is-open');
			if (restoreFocus) btn.focus();
		}

		tops.forEach(function (btn) {
			var li = btn.parentElement;

			btn.addEventListener('click', function () {
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				// Le survol vient d'ouvrir le panneau : un clic juste après ne doit pas le refermer.
				if (expanded && !isMobile() && Date.now() - openedByHoverAt < 400) return;
				if (expanded) close(btn, false); else open(btn, false);
			});

			li.addEventListener('mouseenter', function () {
				if (isMobile()) return;
				clearTimeout(tClose);
				tOpen = setTimeout(function () { open(btn, true); }, 120);
			});
			li.addEventListener('mouseleave', function () {
				if (isMobile()) return;
				clearTimeout(tOpen);
				tClose = setTimeout(function () { if (openBtn === btn) close(btn, false); }, 250);
			});
		});

		// Rubriques : desktop = une seule active (maître-détail) ; mobile = déplier / replier.
		panels.forEach(function (panel) {
			var rails = railsOf(panel);
			var tHover;

			function activate(rail) {
				rails.forEach(function (r) {
					var on = r === rail;
					r.setAttribute('aria-expanded', on ? 'true' : 'false');
					byId(r.getAttribute('aria-controls')).hidden = !on;
				});
			}
			function toggle(rail) {
				var on = rail.getAttribute('aria-expanded') !== 'true';
				rail.setAttribute('aria-expanded', on ? 'true' : 'false');
				byId(rail.getAttribute('aria-controls')).hidden = !on;
			}

			rails.forEach(function (rail, i) {
				rail.addEventListener('click', function () {
					if (isMobile()) toggle(rail); else activate(rail);
				});
				rail.addEventListener('mouseenter', function () {
					if (isMobile()) return;
					clearTimeout(tHover);
					// petit délai : évite de changer de rubrique en traversant la liste en diagonale
					tHover = setTimeout(function () { activate(rail); }, 90);
				});
				rail.addEventListener('mouseleave', function () { clearTimeout(tHover); });
				rail.addEventListener('keydown', function (e) {
					if (isMobile()) return;
					if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
						e.preventDefault();
						var next = rails[(i + (e.key === 'ArrowDown' ? 1 : -1) + rails.length) % rails.length];
						next.focus();
						activate(next);
					} else if (e.key === 'ArrowRight') {
						e.preventDefault();
						activate(rail);
						var f = byId(rail.getAttribute('aria-controls')).querySelector('.tmm-groups a[href]');
						if (f) f.focus();
					}
				});
			});
		});

		// Mobile : bouton Menu
		if (burger) {
			burger.addEventListener('click', function () {
				var on = burger.getAttribute('aria-expanded') !== 'true';
				burger.setAttribute('aria-expanded', on ? 'true' : 'false');
				// La feuille s'ouvre juste sous l'en-tête, quelle que soit sa hauteur.
				if (on) bar.style.top = Math.max(0, Math.round((header || nav).getBoundingClientRect().bottom)) + 'px';
				nav.classList.toggle('is-mobile-open', on);
				document.documentElement.classList.toggle('tmm-lock', on);
				if (!on && openBtn) close(openBtn, false);
			});
		}

		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') return;
			if (openBtn) { close(openBtn, true); }
			else if (burger && nav.classList.contains('is-mobile-open')) { burger.click(); burger.focus(); }
		});
		// Desktop : le focus clavier quitte le menu → on referme
		nav.addEventListener('focusout', function (e) {
			if (!openBtn || isMobile()) return;
			if (e.relatedTarget && !nav.contains(e.relatedTarget)) close(openBtn, false);
		});
		document.addEventListener('click', function (e) {
			if (openBtn && !isMobile() && !nav.contains(e.target)) close(openBtn, false);
		});

		var onChange = function () {
			if (openBtn) close(openBtn, false);
			if (burger && !isMobile() && nav.classList.contains('is-mobile-open')) burger.click();
			bar.style.top = '';
			layout();
		};
		if (mq.addEventListener) mq.addEventListener('change', onChange); else mq.addListener(onChange);
		layout();
	}

	function boot() { each(document.querySelectorAll('[data-tmm]'), init); }
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
