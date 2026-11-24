/*!
 * Tidewrack 1.1.1 — Chaotic Fish Design Studio
 * Client-side tag filtering, search, sort and reveal for mod_tidewrack.
 * No dependencies. Every module instance on a page runs independently.
 */
(function () {
	'use strict';

	var reduced = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var SORTS = {
		pub_desc: function (a, b) { return b.pub - a.pub || a.i - b.i; },
		pub_asc: function (a, b) { return a.pub - b.pub || a.i - b.i; },
		mod_desc: function (a, b) { return b.mod - a.mod || a.i - b.i; },
		mod_asc: function (a, b) { return a.mod - b.mod || a.i - b.i; },
		title_asc: function (a, b) { return a.title.localeCompare(b.title) || a.i - b.i; }
	};
	var instances = [];
	var READ_KEY = 'twk-beachcombed';

	/** Read marks live in the visitor's own browser; storage can be missing or blocked, so every access is guarded. */
	var Sand = {
		load: function () {
			try {
				var list = JSON.parse(window.localStorage.getItem(READ_KEY) || '[]');
				return Array.isArray(list) ? list.map(String) : [];
			} catch (e) { return []; }
		},
		add: function (id) {
			var list = this.load();
			if (list.indexOf(id) !== -1) { return; }
			list.push(id);
			try { window.localStorage.setItem(READ_KEY, JSON.stringify(list.slice(-500))); } catch (e) { /* private mode */ }
		},
		clear: function () {
			try { window.localStorage.removeItem(READ_KEY); } catch (e) { /* private mode */ }
		}
	};

	function fmt(str) {
		var args = Array.prototype.slice.call(arguments, 1);
		var n = 0;
		return String(str || '').replace(/%(\d+)\$s|%s/g, function (m, pos) {
			return args[pos ? pos - 1 : n++];
		});
	}

	function Tidewrack(root) {
		this.root = root;
		this.list = root.querySelector('[data-twk-items]');
		this.style = root.getAttribute('data-twk-style') || 'grid';
		this.view = this.style;
		this.initial = parseInt(root.getAttribute('data-twk-initial'), 10) || 0;
		this.step = parseInt(root.getAttribute('data-twk-step'), 10) || 6;
		this.shown = this.initial;
		this.sort = root.getAttribute('data-twk-sort') || 'pub_desc';
		this.mode = root.getAttribute('data-twk-mode') === 'all' ? 'all' : 'any';
		this.useUrl = root.getAttribute('data-twk-url') === '1';
		this.media = root.getAttribute('data-twk-media') || '';
		this.extras = root.getAttribute('data-twk-extras') === '1';
		this.tags = [];
		this.q = '';
		this.s = {
			showing: root.getAttribute('data-twk-s-showing') || 'Showing %1$s of %2$s',
			one: root.getAttribute('data-twk-s-showing-one') || 'Showing 1 of 1',
			none: root.getAttribute('data-twk-s-none') || '',
			caught: root.getAttribute('data-twk-s-caught') || '%s'
		};
		this.readMarks = root.getAttribute('data-twk-readmarks') === '1';

		this.cards = Array.prototype.map.call(this.list.querySelectorAll('[data-twk-card]'), function (el, i) {
			return {
				el: el,
				i: i,
				tags: el.getAttribute('data-twk-tags').trim().split(/\s+/).filter(Boolean),
				pub: +el.getAttribute('data-twk-pub') || 0,
				mod: +el.getAttribute('data-twk-mod') || 0,
				title: el.getAttribute('data-twk-title') || '',
				text: el.getAttribute('data-twk-text') || ''
			};
		});

		this.q$ = root.querySelector('[data-twk-q]');
		this.sort$ = root.querySelector('[data-twk-sortsel]');
		this.more$ = root.querySelector('[data-twk-more]');
		this.empty$ = root.querySelector('[data-twk-empty]');
		this.status$ = root.querySelector('[data-twk-status]');
		this.clear$ = root.querySelector('[data-twk-clear]');
		this.cast$ = root.querySelector('[data-twk-cast]');
		this.forget$ = root.querySelector('[data-twk-forget]');

		var bar = root.querySelector('.twk-bar');
		if (bar) { bar.hidden = false; }
		if (this.clear$) {
			// aria-disabled instead of disabled, so the long-press Extras trigger still receives touches.
			this.clear$.disabled = false;
			this.clear$.setAttribute('aria-disabled', 'true');
		}

		this.chips = Array.prototype.map.call(root.querySelectorAll('[data-twk-chip]'), function (el) {
			return { el: el, id: el.getAttribute('data-twk-chip'), count: el.querySelector('[data-twk-count]') };
		});

		this.readUrl();
		this.bind();
		root.classList.add('twk-ready');
		this.apply(false);
		this.watchMasonry();
		this.markRead();
	}

	Tidewrack.prototype.bind = function () {
		var self = this;
		var root = this.root;
		var timer;

		root.addEventListener('click', function (ev) {
			var link = ev.target.closest('.twk-link');
			if (link && self.readMarks) { self.remember(link); }

			var t = ev.target.closest('[data-twk-chip],[data-twk-tagbtn],[data-twk-modebtn],[data-twk-view],[data-twk-clear],[data-twk-more],[data-twk-cast],[data-twk-forget],[data-twk-staff]');
			if (!t || !root.contains(t)) { return; }

			if (t.hasAttribute('data-twk-chip') || t.hasAttribute('data-twk-tagbtn')) {
				ev.preventDefault();
				if (t.getAttribute('aria-disabled') === 'true') { return; }
				self.toggleTag(t.getAttribute('data-twk-chip') || t.getAttribute('data-twk-tagbtn'));
			} else if (t.hasAttribute('data-twk-modebtn')) {
				self.mode = t.getAttribute('data-twk-modebtn');
				self.resetShown();
				self.apply(true);
			} else if (t.hasAttribute('data-twk-view')) {
				self.setView(t.getAttribute('data-twk-view'));
			} else if (t.hasAttribute('data-twk-clear')) {
				if (t.getAttribute('aria-disabled') === 'true' || self.longPressed) { self.longPressed = false; return; }
				self.tags = [];
				self.q = '';
				if (self.q$) { self.q$.value = ''; }
				self.resetShown();
				self.apply(true);
			} else if (t.hasAttribute('data-twk-more')) {
				self.showMore();
			} else if (t.hasAttribute('data-twk-staff')) {
				Extras.summon('random', self);
			} else if (t.hasAttribute('data-twk-cast')) {
				self.castLine();
			} else if (t.hasAttribute('data-twk-forget')) {
				Sand.clear();
				instances.forEach(function (inst) { inst.markRead(); });
				var first = self.root.querySelector('[data-twk-chip],[data-twk-q],[data-twk-cast],.twk-link');
				if (first) { first.focus(); }
			}
		});

		// Middle-click and "open in new tab" count as reading too.
		root.addEventListener('auxclick', function (ev) {
			var link = ev.target.closest('.twk-link');
			if (link && self.readMarks && ev.button === 1) { self.remember(link); }
		});

		if (this.q$) {
			this.q$.addEventListener('input', function () {
				clearTimeout(timer);
				timer = setTimeout(function () {
					self.q = self.q$.value.trim().toLowerCase();
					self.resetShown();
					self.apply(true);
				}, 160);
			});
		}

		if (this.q$) {
			this.q$.addEventListener('keydown', function (ev) {
				if (ev.key !== 'Escape' || !self.q$.value) { return; }
				ev.preventDefault();
				clearTimeout(timer);
				self.q$.value = '';
				self.q = '';
				self.resetShown();
				self.apply(true);
			});
		}

		if (this.sort$) {
			this.sort$.addEventListener('change', function () {
				self.sort = SORTS[self.sort$.value] ? self.sort$.value : 'pub_desc';
				self.apply(true);
			});
		}
	};

	Tidewrack.prototype.toggleTag = function (id) {
		var at = this.tags.indexOf(id);
		if (at === -1) { this.tags.push(id); } else { this.tags.splice(at, 1); }
		this.resetShown();
		this.apply(true);
	};

	Tidewrack.prototype.resetShown = function () {
		this.shown = this.initial;
	};

	Tidewrack.prototype.setView = function (view) {
		this.root.classList.remove('twk--' + this.view);
		this.view = view === 'list' ? 'list' : this.style;
		this.root.classList.add('twk--' + this.view);
		this.apply(true);
	};

	Tidewrack.prototype.matchTags = function (c, tags) {
		if (!tags.length) { return true; }
		return this.mode === 'all'
			? tags.every(function (t) { return c.tags.indexOf(t) !== -1; })
			: tags.some(function (t) { return c.tags.indexOf(t) !== -1; });
	};

	Tidewrack.prototype.matchText = function (c) {
		if (!this.q) { return true; }
		var words = this.q.split(/\s+/);
		for (var i = 0; i < words.length; i++) {
			if (c.title.indexOf(words[i]) === -1 && c.text.indexOf(words[i]) === -1) { return false; }
		}
		return true;
	};

	Tidewrack.prototype.matches = function (c) {
		return this.matchTags(c, this.tags) && this.matchText(c);
	};

	/**
	 * Live chip counts: how many articles that tag would show alongside the current search
	 * (and, in All mode, the tags already picked). Unpicked tags that would show nothing are dimmed.
	 */
	Tidewrack.prototype.updateChips = function () {
		var self = this;
		var pool = this.cards.filter(function (c) { return self.matchText(c); });
		if (this.mode === 'all' && this.tags.length) {
			pool = pool.filter(function (c) { return self.matchTags(c, self.tags); });
		}
		this.chips.forEach(function (chip) {
			var n = 0;
			pool.forEach(function (c) { if (c.tags.indexOf(chip.id) !== -1) { n++; } });
			var on = self.tags.indexOf(chip.id) !== -1;
			var dim = !on && n === 0;
			if (chip.count) { chip.count.textContent = String(n); }
			chip.el.classList.toggle('twk-chip--empty', dim);
			chip.el.setAttribute('aria-disabled', dim ? 'true' : 'false');
		});
	};

	/** Masonry in reading order: a CSS grid of 1px rows, each card spanning its own height. */
	Tidewrack.prototype.layoutMasonry = function () {
		var on = this.view === 'masonry';
		var gap = on ? parseFloat(window.getComputedStyle(this.list).columnGap) || 0 : 0;
		this.cards.forEach(function (c) {
			if (!on || c.el.hidden) { c.el.style.removeProperty('grid-row-end'); return; }
			c.el.style.gridRowEnd = 'span ' + Math.max(1, Math.ceil(c.el.offsetHeight + gap));
		});
	};

	Tidewrack.prototype.watchMasonry = function () {
		var self = this;
		var queued = false;
		if (typeof window.ResizeObserver !== 'function') { return; }
		// Card heights change as images load, fonts swap or the viewport resizes.
		var ro = new window.ResizeObserver(function () {
			if (queued || self.view !== 'masonry') { return; }
			queued = true;
			window.requestAnimationFrame(function () { queued = false; self.layoutMasonry(); });
		});
		this.cards.forEach(function (c) { ro.observe(c.el); });
	};

	/** Tancho or Ziggy, at random, each time the "nothing matches" message appears. */
	Tidewrack.prototype.pickMascot = function () {
		var all = this.empty$.querySelectorAll('[data-twk-mascot]');
		if (all.length < 2) { return; }
		var pick = Math.floor(Math.random() * all.length);
		Array.prototype.forEach.call(all, function (el, i) { el.hidden = i !== pick; });
	};

	/* ---------- Beachcombed marks ---------- */

	Tidewrack.prototype.remember = function (link) {
		var card = link.closest('[data-twk-card]');
		if (!card) { return; }
		Sand.add(card.getAttribute('data-twk-card'));
		this.markRead();
	};

	Tidewrack.prototype.markRead = function () {
		if (!this.readMarks) { return; }
		var read = Sand.load();
		var any = false;
		this.cards.forEach(function (c) {
			var on = read.indexOf(c.el.getAttribute('data-twk-card')) !== -1;
			var mark = c.el.querySelector('[data-twk-readmark]');
			c.el.classList.toggle('twk-card--read', on);
			if (mark) { mark.hidden = !on; }
			any = any || on;
		});
		if (this.forget$) { this.forget$.hidden = !any; }
	};

	/* ---------- Cast a line: bring a random article from the current filters into view ---------- */

	Tidewrack.prototype.castLine = function () {
		var self = this;
		var pool = this.hits.filter(function (c) { return c !== self.lastCaught; });
		if (!pool.length) { pool = this.hits; }
		if (!pool.length) { return; }

		var c = pool[Math.floor(Math.random() * pool.length)];
		var at = this.hits.indexOf(c);
		this.lastCaught = c;

		// Reveal enough Show More steps to include the catch.
		if (this.shown > 0 && at >= this.shown) {
			this.shown = this.initial + Math.ceil((at + 1 - this.initial) / this.step) * this.step;
			this.apply(true);
		}

		var link = c.el.querySelector('.twk-link');
		c.el.scrollIntoView({ block: 'center', behavior: reduced.matches ? 'auto' : 'smooth' });
		if (link) { link.focus({ preventScroll: true }); }
		c.el.classList.remove('twk-caught');
		void c.el.offsetWidth;
		c.el.classList.add('twk-caught');
		setTimeout(function () { c.el.classList.remove('twk-caught'); }, 1600);
		if (this.status$ && link) { this.status$.textContent = fmt(this.s.caught, link.textContent.trim()); }
	};

	Tidewrack.prototype.showMore = function () {
		var before = this.visible.length;
		var btn = this.more$;
		this.shown += this.step;
		this.stagger = true;
		if (btn && !reduced.matches) {
			btn.classList.remove('twk-more--rolling');
			void btn.offsetWidth;
			btn.classList.add('twk-more--rolling');
			setTimeout(function () { btn.classList.remove('twk-more--rolling'); }, 900);
		}
		this.apply(true);
		this.stagger = false;
		// Move focus to the first newly revealed card so keyboard users land on the new content.
		var next = this.visible[before];
		if (next) {
			var link = next.el.querySelector('.twk-link');
			if (link) { link.focus({ preventScroll: false }); }
		}
	};

	Tidewrack.prototype.apply = function (animate) {
		var self = this;
		var motion = animate && !reduced.matches && typeof this.list.animate === 'function';
		var first = new Map();

		if (motion) {
			this.cards.forEach(function (c) {
				if (!c.el.hidden) { first.set(c.el, c.el.getBoundingClientRect()); }
			});
		}

		var hits = this.cards.filter(function (c) { return self.matches(c); }).sort(SORTS[this.sort] || SORTS.pub_desc);
		var misses = this.cards.filter(function (c) { return hits.indexOf(c) === -1; });
		var limit = this.shown > 0 ? this.shown : hits.length;
		this.hits = hits;
		this.visible = hits.slice(0, limit);

		var frag = document.createDocumentFragment();
		hits.concat(misses).forEach(function (c) { frag.appendChild(c.el); });
		this.list.appendChild(frag);

		this.cards.forEach(function (c) {
			var at = self.visible.indexOf(c);
			c.el.hidden = at === -1;
			c.el.classList.toggle('twk-lead', at === 0);
			c.el.classList.toggle('twk-alt', at % 2 === 1);
			if (at !== -1) {
				c.el.style.setProperty('--twk-frame', '"' + (at + 1) + 'A"');
				c.el.style.setProperty('--twk-row', String(at + 1));
			}
		});

		// Chip and in-card tag button state.
		Array.prototype.forEach.call(this.root.querySelectorAll('[data-twk-chip],[data-twk-tagbtn]'), function (b) {
			var id = b.getAttribute('data-twk-chip') || b.getAttribute('data-twk-tagbtn');
			b.setAttribute('aria-pressed', self.tags.indexOf(id) !== -1 ? 'true' : 'false');
		});
		Array.prototype.forEach.call(this.root.querySelectorAll('[data-twk-modebtn]'), function (b) {
			var on = b.getAttribute('data-twk-modebtn') === self.mode;
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
			b.classList.toggle('active', on);
		});
		Array.prototype.forEach.call(this.root.querySelectorAll('[data-twk-view]'), function (b) {
			var on = (b.getAttribute('data-twk-view') === 'list') === (self.view === 'list');
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
			b.classList.toggle('active', on);
		});
		this.updateChips();
		this.layoutMasonry();
		if (this.sort$) { this.sort$.value = this.sort; }
		if (this.clear$) { this.clear$.setAttribute('aria-disabled', this.tags.length || this.q ? 'false' : 'true'); }

		if (this.empty$) {
			var wasHidden = this.empty$.hidden;
			this.empty$.hidden = hits.length !== 0;
			if (wasHidden && !this.empty$.hidden) { this.pickMascot(); }
		}
		if (this.cast$) { this.cast$.disabled = hits.length === 0; }
		if (this.more$) { this.more$.hidden = hits.length <= limit; }
		if (this.status$) { this.status$.textContent = !hits.length ? this.s.none : hits.length === 1 ? this.s.one : fmt(this.s.showing, this.visible.length, hits.length); }

		this.writeUrl();

		if (!motion) { return; }

		var fresh = 0;
		var stagger = this.stagger;
		this.visible.forEach(function (c) {
			var was = first.get(c.el);
			if (was) {
				var now = c.el.getBoundingClientRect();
				var dx = was.left - now.left;
				var dy = was.top - now.top;
				if (dx || dy) {
					c.el.animate([{ transform: 'translate(' + dx + 'px,' + dy + 'px)' }, { transform: 'none' }], { duration: 380, easing: 'cubic-bezier(.2,.8,.2,1)' });
				}
			} else {
				// The next tide rolls in: new cards rise in one after another.
				c.el.animate(
					[{ opacity: 0, transform: stagger ? 'translateY(28px) rotate(-1deg)' : 'translateY(12px) scale(.98)' }, { opacity: 1, transform: 'none' }],
					{ duration: stagger ? 520 : 320, delay: stagger ? fresh++ * 70 : 0, easing: 'cubic-bezier(.2,.9,.3,1.2)', fill: 'backwards' }
				);
			}
		});
	};

	// URL state lives in the hash as  #twk-12:tags=3,7;mode=all;q=goat;sort=mod_desc;view=list
	Tidewrack.prototype.readUrl = function () {
		if (!this.useUrl) { return; }
		var prefix = '#' + this.root.id + ':';
		var hash = window.location.hash;
		if (hash.indexOf(prefix) !== 0) { return; }
		var self = this;
		hash.slice(prefix.length).split(';').forEach(function (pair) {
			var kv = pair.split('=');
			var v = decodeURIComponent(kv[1] || '');
			if (kv[0] === 'tags') { self.tags = v.split(',').filter(function (t) { return /^\d+$/.test(t); }); }
			if (kv[0] === 'mode') { self.mode = v === 'all' ? 'all' : 'any'; }
			if (kv[0] === 'q') { self.q = v.toLowerCase(); if (self.q$) { self.q$.value = v; } }
			if (kv[0] === 'sort' && SORTS[v]) { self.sort = v; }
			if (kv[0] === 'view' && v === 'list' && self.root.querySelector('[data-twk-view]')) {
				self.root.classList.remove('twk--' + self.view);
				self.view = 'list';
				self.root.classList.add('twk--list');
			}
		});
		var root = this.root;
		window.requestAnimationFrame(function () { root.scrollIntoView({ block: 'start' }); });
	};

	Tidewrack.prototype.writeUrl = function () {
		if (!this.useUrl || !window.history.replaceState) { return; }
		var parts = [];
		if (this.tags.length) { parts.push('tags=' + this.tags.join(',')); }
		if (this.tags.length > 1 && this.mode !== 'any') { parts.push('mode=' + this.mode); }
		if (this.q) { parts.push('q=' + encodeURIComponent(this.q)); }
		if (this.sort !== this.root.getAttribute('data-twk-sort')) { parts.push('sort=' + this.sort); }
		if (this.view !== this.style) { parts.push('view=' + this.view); }

		var prefix = '#' + this.root.id + ':';
		var hash = window.location.hash;
		var ours = hash.indexOf(prefix) === 0;
		var base = window.location.pathname + window.location.search;

		if (parts.length) {
			window.history.replaceState(window.history.state, '', base + prefix + parts.join(';'));
		} else if (ours) {
			window.history.replaceState(window.history.state, '', base);
		}
	};

	/* ---------- Extras: the staff occasionally drops by ---------- */

	var Extras = {
		busy: false,
		buffer: '',

		target: function () {
			var best = null;
			var vh = window.innerHeight;
			instances.forEach(function (inst) {
				if (!inst.extras || best) { return; }
				var r = inst.root.getBoundingClientRect();
				if (r.bottom > 0 && r.top < vh) { best = inst; }
			});
			if (!best) {
				instances.some(function (inst) { if (inst.extras) { best = inst; } return !!best; });
			}
			return best;
		},

		onScreenCards: function (inst) {
			var vh = window.innerHeight;
			return inst.visible.filter(function (c) {
				var r = c.el.getBoundingClientRect();
				return r.bottom > 40 && r.top < vh - 40;
			});
		},

		wrasse: function (inst) {
			var root = inst.root;
			var self = this;
			var cards = this.onScreenCards(inst);
			if (!cards.length) { cards = inst.visible.slice(0, 3); }
			if (!cards.length) { return this.done(); }

			var src = root.getAttribute('data-twk-img-wrasse') || inst.media + 'images/cleaner-wrasse.svg';
			var rootBox = root.getBoundingClientRect();
			var w = root.clientWidth;
			var fishW = Math.min(170, Math.max(110, window.innerWidth * 0.14));
			var fishH = fishW * 400 / 989;
			var withMedia = cards.filter(function (c) { return c.el.querySelector('.twk-media'); });
			var lane = (withMedia.length ? withMedia : cards)[Math.floor((withMedia.length || cards.length) / 2)];
			var laneBox = (lane.el.querySelector('.twk-media') || lane.el).getBoundingClientRect();
			// Fish top edge, in module coordinates, centred on the lane card's photo.
			var y = laneBox.top - rootBox.top + laneBox.height / 2 - fishH / 2;
			var dur = Math.max(4200, w * 6);
			var swims = [];

			function fish(top) {
				var img = document.createElement('img');
				img.src = src;
				img.alt = '';
				img.className = 'twk-wrasse';
				img.setAttribute('aria-hidden', 'true');
				img.style.top = top + 'px';
				return img;
			}

			function finish() {
				swims.forEach(function (el) { el.remove(); });
				self.done();
			}

			// He swims "behind" the page: one copy inside each photo in his lane, every copy on the
			// same clock, so he only shows through the photos and vanishes behind margins and text.
			var hosts = [];
			inst.visible.forEach(function (c) {
				var media = c.el.querySelector('.twk-media');
				if (!media) { return; }
				var r = media.getBoundingClientRect();
				var top = r.top - rootBox.top;
				if (y + fishH < top || y > top + r.height) { return; }
				hosts.push({ card: c, media: media, left: r.left - rootBox.left, top: top, width: r.width });
			});

			if (!hosts.length) {
				// Text-only cards: fall back to a free swim across the module.
				var free = fish(y);
				root.appendChild(free);
				swims.push(free);
				hosts.push({ card: null, media: null, left: 0, top: 0, el: free });
			} else {
				hosts.forEach(function (h) {
					var tank = document.createElement('span');
					tank.className = 'twk-swim';
					tank.setAttribute('aria-hidden', 'true');
					h.el = fish(y - h.top);
					tank.appendChild(h.el);
					h.media.appendChild(tank);
					swims.push(tank);
				});
			}

			if (reduced.matches) {
				var hl = hosts.filter(function (h) { return h.card === lane; })[0] || hosts[0];
				hosts.forEach(function (h) { if (h !== hl) { h.el.style.display = 'none'; } });
				hl.el.style.transform = 'translateX(' + ((hl.width || w) / 2 - fishW / 2) + 'px)';
				hl.el.animate([{ opacity: 0 }, { opacity: 1, offset: 0.2 }, { opacity: 1, offset: 0.8 }, { opacity: 0 }], { duration: 2600 })
					.onfinish = finish;
				return;
			}

			// The artwork faces left, so he swims right → left across the whole module.
			hosts.forEach(function (h, i) {
				var o = h.left;
				var anim = h.el.animate([
					{ transform: 'translate(' + (w + 40 - o) + 'px,0)' },
					{ transform: 'translate(' + (w * 0.66 - o) + 'px,-14px)', offset: 0.33 },
					{ transform: 'translate(' + (w * 0.33 - o) + 'px,10px)', offset: 0.66 },
					{ transform: 'translate(' + (-fishW - 50 - o) + 'px,0)' }
				], { duration: dur, easing: 'linear', fill: 'forwards' });
				if (i === 0) { anim.onfinish = finish; }
			});

			// Nibble each photo-card in his lane as his nose passes its middle.
			hosts.forEach(function (h) {
				if (!h.card) { return; }
				var cx = h.left + h.width / 2;
				var at = (w + 40 - cx) / (w + fishW + 90) * dur;
				setTimeout(function () {
					h.card.el.classList.add('twk-nibbled');
					setTimeout(function () { h.card.el.classList.remove('twk-nibbled'); }, 700);
				}, Math.max(0, at));
			});
		},

		kokido: function (inst) {
			var cards = this.onScreenCards(inst);
			if (!cards.length) { cards = inst.visible.slice(0, 3); }
			if (!cards.length) { return this.done(); }

			var card = cards[Math.floor(Math.random() * cards.length)].el;
			// He pops up from the bottom edge of the card's photo; with no photo, over the card's top edge.
			var host = card.querySelector('.twk-media') || card;
			var box = document.createElement('span');
			var cat = document.createElement('img');
			var self = this;
			var gone = false;

			box.className = 'twk-kokido' + (host === card ? ' twk-kokido--edge' : '');
			box.setAttribute('aria-hidden', 'true');
			box.style.left = (10 + Math.random() * 55) + '%';
			cat.src = inst.root.getAttribute('data-twk-img-kokido') || inst.media + 'images/kokido-peek--240.webp';
			cat.alt = '';
			box.appendChild(cat);
			host.appendChild(box);

			function duck() {
				if (gone) { return; }
				gone = true;
				box.classList.remove('is-up');
				setTimeout(function () { box.remove(); self.done(); }, reduced.matches ? 300 : 450);
			}

			cat.addEventListener('pointerenter', duck);
			cat.addEventListener('click', function (ev) { ev.preventDefault(); ev.stopPropagation(); duck(); });
			// Double rAF so the start position paints before the spring.
			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(function () { box.classList.add('is-up'); });
			});
			setTimeout(duck, 5000);
		},

		img: function (inst, name) {
			return inst.root.getAttribute('data-twk-img-' + name) || inst.media + 'images/' + name + '.svg';
		},

		/** Hawke walks in from the card's right edge, eats a tag chip, and wanders off. The chip grows back. */
		hawke: function (inst) {
			var self = this;
			var cards = this.onScreenCards(inst).filter(function (c) { return c.el.querySelector('.twk-tag'); });
			if (!cards.length) { return this.done(); }

			var card = cards[Math.floor(Math.random() * cards.length)].el;
			var tags = card.querySelectorAll('.twk-tag');
			var tag = tags[Math.floor(Math.random() * tags.length)];
			var cb = card.getBoundingClientRect();
			var tb = tag.getBoundingClientRect();
			var gw = 46;
			var gh = gw * 24 / 32;
			var goat = document.createElement('img');
			goat.src = this.img(inst, 'hawke');
			goat.alt = '';
			goat.className = 'twk-hawke';
			goat.setAttribute('aria-hidden', 'true');
			goat.style.top = (tb.bottom - cb.top - gh + 2) + 'px';
			card.appendChild(goat);

			var startX = cb.width - gw * 0.4;
			var eatX = tb.right - cb.left - 6;
			var walk = Math.max(500, (startX - eatX) * 9);

			function chomp() {
				tag.classList.add('twk-tag--chomp');
			}
			function regrow() {
				tag.classList.remove('twk-tag--chomp');
				tag.classList.add('twk-tag--regrow');
				setTimeout(function () { tag.classList.remove('twk-tag--regrow'); }, 600);
			}

			if (reduced.matches) {
				goat.style.transform = 'translateX(' + eatX + 'px) scaleX(-1)';
				goat.animate([{ opacity: 0 }, { opacity: 1, offset: .2 }, { opacity: 1, offset: .8 }, { opacity: 0 }], { duration: 2600 }).onfinish = function () {
					goat.remove(); regrow(); self.done();
				};
				setTimeout(chomp, 700);
				return;
			}

			// The artwork faces right; scaleX(-1) points him left, toward the chip.
			goat.animate([
				{ transform: 'translateX(' + startX + 'px) scaleX(-1)', opacity: 0 },
				{ transform: 'translateX(' + (startX - 12) + 'px) scaleX(-1)', opacity: 1, offset: .1 },
				{ transform: 'translateX(' + eatX + 'px) scaleX(-1)', opacity: 1 }
			], { duration: walk, easing: 'ease-out', fill: 'forwards' }).onfinish = function () {
				goat.classList.add('is-chewing');
				chomp();
				setTimeout(function () {
					goat.classList.remove('is-chewing');
					goat.animate([
						{ transform: 'translateX(' + eatX + 'px) scaleX(1)', opacity: 1 },
						{ transform: 'translateX(' + startX + 'px) scaleX(1)', opacity: 1, offset: .85 },
						{ transform: 'translateX(' + (startX + 10) + 'px) scaleX(1)', opacity: 0 }
					], { duration: walk, easing: 'ease-in', fill: 'forwards' }).onfinish = function () {
						goat.remove();
						setTimeout(function () { regrow(); self.done(); }, 900);
					};
				}, 1300);
			};
		},

		/** Tancho trots down the Tide Line spine, or along the top of the first row he can see, kicking up dust. */
		tancho: function (inst) {
			var self = this;
			var cards = this.onScreenCards(inst);
			if (!cards.length) { cards = inst.visible.slice(0, 1); }
			if (!cards.length) { return this.done(); }

			var root = inst.root;
			var rb = root.getBoundingClientRect();
			var hw = 54;
			var hh = hw * 24 / 32;
			var horse = document.createElement('img');
			var spine = inst.view === 'tideline';
			var from;
			var to;

			horse.src = this.img(inst, 'tancho');
			horse.alt = '';
			horse.className = 'twk-tancho-run';
			horse.setAttribute('aria-hidden', 'true');

			if (spine) {
				var items = inst.list.getBoundingClientRect();
				var line = window.getComputedStyle(inst.list, '::before');
				var x = items.left - rb.left + (parseFloat(line.left) || items.width / 2) - hw / 2;
				var top = Math.max(items.top, 0) - rb.top;
				var bottom = Math.min(items.bottom, window.innerHeight) - rb.top - hh;
				if (window.getComputedStyle(inst.list, '::before').transform !== 'none') { x += 1.5; }
				horse.style.left = x + 'px';
				horse.style.top = '0px';
				from = 'translateY(' + top + 'px)';
				to = 'translateY(' + Math.max(top + 120, bottom) + 'px)';
			} else {
				var row = cards[0].el.getBoundingClientRect();
				horse.style.left = '0px';
				horse.style.top = (row.top - rb.top - hh + 3) + 'px';
				from = 'translateX(' + (-hw) + 'px)';
				to = 'translateX(' + root.clientWidth + 'px)';
			}
			root.appendChild(horse);

			if (reduced.matches) {
				horse.style.transform = spine ? from : 'translateX(' + (root.clientWidth / 2 - hw / 2) + 'px)';
				horse.animate([{ opacity: 0 }, { opacity: 1, offset: .2 }, { opacity: 1, offset: .8 }, { opacity: 0 }], { duration: 2600 }).onfinish = function () {
					horse.remove(); self.done();
				};
				return;
			}

			var dur = spine ? 3200 : Math.max(3400, root.clientWidth * 4.5);
			horse.animate([{ transform: from }, { transform: to }], { duration: dur, easing: 'linear', fill: 'forwards' }).onfinish = function () {
				horse.remove(); self.done();
			};

			// Dust kicked up behind him every few strides.
			var puffs = 0;
			var timer = setInterval(function () {
				if (!horse.isConnected || puffs++ > 30) { clearInterval(timer); return; }
				var hb = horse.getBoundingClientRect();
				var dust = document.createElement('span');
				dust.className = 'twk-dust';
				dust.setAttribute('aria-hidden', 'true');
				dust.style.left = (spine ? hb.left - rb.left + hw / 2 : hb.left - rb.left + 6) + 'px';
				dust.style.top = (spine ? hb.top - rb.top + 4 : hb.bottom - rb.top - 6) + 'px';
				root.appendChild(dust);
				setTimeout(function () { dust.remove(); }, 800);
			}, 160);
		},

		summon: function (who, inst) {
			inst = inst || this.target();
			if (!inst || this.busy) { return; }
			this.busy = true;
			if (who === 'random') { who = ['wrasse', 'kokido', 'hawke', 'tancho'][Math.floor(Math.random() * 4)]; }
			this[who](inst);
		},

		done: function () { this.busy = false; },

		init: function () {
			var self = this;

			document.addEventListener('keydown', function (ev) {
				var el = ev.target;
				if (ev.ctrlKey || ev.metaKey || ev.altKey || !ev.key || ev.key.length !== 1) { return; }
				if (el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName))) { return; }
				self.buffer = (self.buffer + ev.key.toLowerCase()).slice(-12);
				if (/(fishie|claude)$/.test(self.buffer)) { self.buffer = ''; self.summon('wrasse'); }
				else if (/kokido$/.test(self.buffer)) { self.buffer = ''; self.summon('kokido'); }
				else if (/hawke$/.test(self.buffer)) { self.buffer = ''; self.summon('hawke'); }
				else if (/tancho$/.test(self.buffer)) { self.buffer = ''; self.summon('tancho'); }
			});

			// Touch: long-press the Clear button.
			instances.forEach(function (inst) {
				var btn = inst.clear$;
				var timer;
				if (!inst.extras || !btn) { return; }
				btn.addEventListener('pointerdown', function () {
					timer = setTimeout(function () { inst.longPressed = true; self.summon('random', inst); }, 650);
				});
				['pointerup', 'pointerleave', 'pointercancel'].forEach(function (t) {
					btn.addEventListener(t, function () { clearTimeout(timer); });
				});
				btn.addEventListener('contextmenu', function (ev) { if (inst.longPressed) { ev.preventDefault(); } });
			});
		}
	};

	function boot() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-twk]'), function (root) {
			if (root.twk || !root.querySelector('[data-twk-items]')) { return; }
			root.twk = new Tidewrack(root);
			instances.push(root.twk);
		});
		if (instances.some(function (i) { return i.extras; })) { Extras.init(); }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
