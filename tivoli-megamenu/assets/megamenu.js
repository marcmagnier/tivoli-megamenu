/* Tivoli Mega Menu – comportement (sans dépendance) */
(function () {
	'use strict';

	var mq = window.matchMedia('(max-width: 980px)');
	var isMobile = function () { return mq.matches; };

	function byId(id) { return document.getElementById(id); }
	function firstFocusable(el) {
		return el && el.querySelector('a[href], button:not([hidden]), input');
	}

	function init(nav) {
		var tops = Array.prototype.slice.call(nav.querySelectorAll('.tmm-top__btn'));
		var burger = nav.querySelector('.tmm-burger');
		var openBtn = null;
		var openedByHoverAt = 0;
		var tOpen, tClose;

		function panelOf(btn) { return byId(btn.getAttribute('aria-controls')); }

		function open(btn, viaHover) {
			if (openBtn && openBtn !== btn) close(openBtn, false);
			btn.setAttribute('aria-expanded', 'true');
			panelOf(btn).hidden = false;
			openBtn = btn;
			nav.classList.add('is-open');
			if (viaHover) openedByHoverAt = Date.now();
		}

		function close(btn, restoreFocus) {
			var panel = panelOf(btn);
			btn.setAttribute('aria-expanded', 'false');
			panel.hidden = true;
			panel.classList.remove('show-detail');
			if (openBtn === btn) openBtn = null;
			if (!openBtn) nav.classList.remove('is-open');
			if (restoreFocus) btn.focus();
		}

		tops.forEach(function (btn) {
			var li = btn.parentElement;

			btn.addEventListener('click', function () {
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				// Le survol vient d'ouvrir le panneau : un clic juste après ne doit pas le refermer.
				if (expanded && Date.now() - openedByHoverAt < 400) return;
				if (expanded) {
					close(btn, false);
				} else {
					open(btn, false);
					if (isMobile()) {
						var f = firstFocusable(panelOf(btn).querySelector('.tmm-rail'));
						if (f) f.focus();
					}
				}
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

		// Colonne des rubriques (maître) ↔ détail
		Array.prototype.forEach.call(nav.querySelectorAll('.tmm-panel'), function (panel) {
			var rails = Array.prototype.slice.call(panel.querySelectorAll('[data-tmm-rail]'));
			var tHover;

			function activate(rail, drill) {
				rails.forEach(function (r) {
					var on = r === rail;
					r.setAttribute('aria-expanded', on ? 'true' : 'false');
					byId(r.getAttribute('aria-controls')).hidden = !on;
				});
				if (drill && isMobile()) {
					panel.classList.add('show-detail');
					var back = byId(rail.getAttribute('aria-controls')).querySelector('[data-tmm-back-rail]');
					if (back) back.focus();
				}
			}

			rails.forEach(function (rail, i) {
				rail.addEventListener('click', function () { activate(rail, true); });
				rail.addEventListener('mouseenter', function () {
					if (isMobile()) return;
					clearTimeout(tHover);
					// petit délai : évite de changer de rubrique en traversant la liste en diagonale
					tHover = setTimeout(function () { activate(rail, false); }, 90);
				});
				rail.addEventListener('mouseleave', function () { clearTimeout(tHover); });
				rail.addEventListener('keydown', function (e) {
					if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
						e.preventDefault();
						var next = rails[(i + (e.key === 'ArrowDown' ? 1 : -1) + rails.length) % rails.length];
						next.focus();
						if (!isMobile()) activate(next, false);
					} else if (e.key === 'ArrowRight' && !isMobile()) {
						e.preventDefault();
						activate(rail, false);
						var f = firstFocusable(byId(rail.getAttribute('aria-controls')).querySelector('.tmm-groups'));
						if (f) f.focus();
					}
				});
			});

			Array.prototype.forEach.call(panel.querySelectorAll('[data-tmm-back-rail]'), function (b) {
				b.addEventListener('click', function () {
					panel.classList.remove('show-detail');
					var current = rails.filter(function (r) { return r.getAttribute('aria-expanded') === 'true'; })[0];
					if (current) current.focus();
				});
			});
			Array.prototype.forEach.call(panel.querySelectorAll('[data-tmm-back]'), function (b) {
				b.addEventListener('click', function () {
					var btn = tops.filter(function (t) { return panelOf(t) === panel; })[0];
					if (btn) close(btn, true);
				});
			});
		});

		// Mobile : bouton Menu
		if (burger) {
			burger.addEventListener('click', function () {
				var on = burger.getAttribute('aria-expanded') !== 'true';
				burger.setAttribute('aria-expanded', on ? 'true' : 'false');
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
			if (openBtn && !nav.contains(e.target)) close(openBtn, false);
		});
		var onChange = function () {
			if (openBtn) close(openBtn, false);
			if (burger && !isMobile() && nav.classList.contains('is-mobile-open')) burger.click();
		};
		if (mq.addEventListener) mq.addEventListener('change', onChange); else mq.addListener(onChange);
	}

	function boot() { Array.prototype.forEach.call(document.querySelectorAll('[data-tmm]'), init); }
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
