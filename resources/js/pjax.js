document.addEventListener('alpine:init', () => {
    Alpine.data('pjax', () => ({
        loading: false,

        init() {
            window.addEventListener('click', this.handleClick.bind(this));
            window.addEventListener('submit', this.handleSubmit.bind(this));
            window.addEventListener('popstate', () => {
                this.loadPage(window.location.href, false);
            });
        },

        handleClick(event) {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const anchor = event.target.closest('a[href]');
            if (!anchor) {
                return;
            }

            // hasAttribute(), pas anchor.dataset.noPjax : un attribut `data-no-pjax` sans
            // valeur (le cas d'usage réel, cf. formulaires de téléchargement) donne une chaîne
            // vide en dataset, qui est falsy en JS — le contournerait silencieusement sinon.
            if (anchor.hasAttribute('download') || anchor.hasAttribute('data-no-pjax') || anchor.target === '_blank' || anchor.getAttribute('rel') === 'external' || anchor.hasAttribute('onclick')) {
                return;
            }

            const href = anchor.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                return;
            }

            const url = new URL(anchor.href, window.location.origin);
            if (url.origin !== window.location.origin) {
                return;
            }

            if (url.pathname === window.location.pathname && url.search === window.location.search) {
                return;
            }

            event.preventDefault();
            this.loadPage(url.href, true);
        },

        // Formulaires GET (filtres) et POST/PUT/PATCH/DELETE (actions) : tous deux
        // interceptés pour éviter le rechargement complet. Les formulaires qui utilisent la
        // modale de confirmation partagée (x-on:submit.prevent="$dispatch('open-confirmation'
        // ...)") ont déjà leur défaut empêché à ce stade — event.defaultPrevented les fait
        // ignorer ici ; leur soumission réelle passe par submitForm(), appelée directement par
        // le bouton "Confirmer" de la modale (voir layouts/app.blade.php).
        handleSubmit(event) {
            if (event.defaultPrevented) {
                return;
            }

            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (form.hasAttribute('data-no-pjax') || form.target === '_blank') {
                return;
            }

            const action = new URL(form.getAttribute('action') || window.location.href, window.location.href);
            if (action.origin !== window.location.origin) {
                return;
            }

            if (form.method.toLowerCase() === 'get') {
                const params = new URLSearchParams();
                for (const [key, value] of new FormData(form).entries()) {
                    if (value instanceof File) {
                        continue;
                    }
                    params.append(key, value);
                }
                action.search = params.toString();

                event.preventDefault();
                this.loadPage(action.href, true);
                return;
            }

            event.preventDefault();
            this.submitForm(form);
        },

        async loadPage(url, push = true) {
            const fallback = () => { window.location.href = url; };
            try {
                this.loading = true;
                const response = await fetch(url, { headers: this.pjaxHeaders() });
                await this.applyResponse(response, url, push, fallback);
            } catch (error) {
                console.error('PJAX error:', error);
                fallback();
            } finally {
                this.loading = false;
            }
        },

        // Soumission d'un formulaire d'action (POST/PUT/PATCH/DELETE — la méthode HTTP réelle
        // reste POST côté navigateur, Laravel lit le spoofing _method posé par @method dans le
        // FormData comme pour une soumission native). Appelée à la fois pour les formulaires
        // sans confirmation (via handleSubmit) et pour ceux qui en ont une, directement par le
        // bouton "Confirmer" de la modale partagée.
        //
        // Le fallback est ici form.submit() et non une navigation GET (contrairement à
        // loadPage) : basculer sur window.location.href pour une action réenverrait la requête
        // en GET (impossible de conserver POST/PATCH/DELETE via une simple navigation), ce qui
        // atteindrait une route qui n'accepte pas cette méthode (405) au lieu de rejouer
        // l'action correctement.
        async submitForm(form) {
            const action = new URL(form.getAttribute('action') || window.location.href, window.location.href);
            const fallback = () => { form.submit(); };

            if (action.origin !== window.location.origin) {
                fallback();
                return;
            }

            try {
                this.loading = true;
                const response = await fetch(action.href, {
                    method: 'POST',
                    headers: this.pjaxHeaders(),
                    body: new FormData(form),
                });

                await this.applyResponse(response, action.href, true, fallback);
            } catch (error) {
                console.error('PJAX submit error:', error);
                fallback();
            } finally {
                this.loading = false;
            }
        },

        // En-têtes communs à toute requête PJAX (navigation ou soumission). Accept: text/html
        // est nécessaire : sans lui, Laravel traite une requête avec X-Requested-With comme
        // "attend du JSON" (Request::expectsJson()) dès que l'en-tête Accept par défaut de
        // fetch() (*/*) est présent — une erreur de validation renverrait alors un 422 JSON
        // au lieu de la redirection HTML habituelle avec les erreurs affichées dans la page.
        pjaxHeaders() {
            return { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' };
        },

        // Logique de rendu partagée entre navigation (loadPage) et soumission (submitForm).
        // `fallback` est fourni par l'appelant car le repli correct diffère selon le contexte
        // (voir submitForm ci-dessus) : jamais de window.location.href codé en dur ici.
        async applyResponse(response, requestedUrl, push, fallback) {
            if (!response.ok) {
                fallback();
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMain = doc.querySelector('main');
            const newTitle = doc.querySelector('title');

            if (!newMain || !newTitle) {
                fallback();
                return;
            }

            // document.querySelector(), pas this.$root : $root est résolu relativement à
            // l'élément x-data le plus proche du point d'appel, pas à celui du composant
            // pjax() lui-même — appelé depuis la modale de confirmation (un x-data imbriqué
            // sous <body x-data="pjax()">), this.$root pointait sur la modale, jamais sur
            // <body>, donc ne trouvait jamais <main>. <main> est de toute façon unique dans
            // le document.
            const main = document.querySelector('main');
            if (!main) {
                fallback();
                return;
            }

            main.innerHTML = newMain.innerHTML;
            document.title = newTitle.textContent || document.title;

            // La surbrillance du lien actif est calculée côté serveur (request()->routeIs()
            // dans components/sidebar/link.blade.php, menu.blade.php et sidebar.blade.php).
            // Ces éléments sont en dehors de <main>, donc on les resynchronise aussi pour
            // refléter la nouvelle page.
            ['sidebar-nav', 'sidebar-profile-link'].forEach((id) => {
                const updated = doc.getElementById(id);
                const current = document.getElementById(id);
                if (updated && current) {
                    current.innerHTML = updated.innerHTML;
                    this.initializeNewContent(current);
                }
            });

            // fetch() suit les redirections silencieusement : si le serveur a redirigé
            // ailleurs que l'URL demandée (ex. EnsurePasswordChanged qui renvoie tout vers
            // /profile, ou une redirection normale après soumission de formulaire),
            // response.url contient la destination réelle. Sans ça, la barre d'adresse
            // afficherait l'URL initiale alors que le contenu affiché est celui d'une tout
            // autre page — trompeur et source de confusion.
            const finalUrl = response.url || requestedUrl;
            if (push) {
                history.pushState({}, '', finalUrl);
            } else if (finalUrl !== requestedUrl) {
                history.replaceState({}, '', finalUrl);
            }

            this.executePageScripts(doc);
            this.initializeNewContent(main);
            window.dispatchEvent(new CustomEvent('pjax:loaded', { detail: { url: finalUrl } }));
        },

        initializeNewContent(container) {
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(container);
            }
        },

        executePageScripts(doc) {
            const scripts = doc.querySelectorAll('script');
            scripts.forEach(oldScript => {
                if (oldScript.type && oldScript.type !== 'text/javascript') {
                    return;
                }
                const script = document.createElement('script');
                if (oldScript.src) {
                    script.src = oldScript.src;
                } else {
                    script.textContent = oldScript.textContent;
                }
                document.body.appendChild(script);
                document.body.removeChild(script);
            });
        },
    }));
});
