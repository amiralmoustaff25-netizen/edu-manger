document.addEventListener('alpine:init', () => {
    Alpine.data('pjax', () => ({
        loading: false,

        init() {
            window.addEventListener('click', this.handleClick.bind(this));
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

            if (anchor.hasAttribute('download') || anchor.dataset.noPjax || anchor.target === '_blank' || anchor.getAttribute('rel') === 'external' || anchor.hasAttribute('onclick')) {
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

        async loadPage(url, push = true) {
            try {
                this.loading = true;
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) {
                    window.location.href = url;
                    return;
                }

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newMain = doc.querySelector('main');
                const newTitle = doc.querySelector('title');

                if (!newMain || !newTitle) {
                    window.location.href = url;
                    return;
                }

                const main = this.$root.querySelector('main');
                if (!main) {
                    window.location.href = url;
                    return;
                }

                main.innerHTML = newMain.innerHTML;
                document.title = newTitle.textContent || document.title;

                // La surbrillance du lien actif est calculée côté serveur (request()->routeIs()
                // dans components/sidebar/link.blade.php et menu.blade.php). La sidebar est en
                // dehors de <main>, donc on la resynchronise aussi pour refléter la nouvelle page.
                const newNav = doc.getElementById('sidebar-nav');
                const nav = this.$root.querySelector('#sidebar-nav');
                if (newNav && nav) {
                    nav.innerHTML = newNav.innerHTML;
                    this.initializeNewContent(nav);
                }

                // fetch() suit les redirections silencieusement : si le serveur a redirigé
                // ailleurs que l'URL cliquée (ex. EnsurePasswordChanged qui renvoie tout vers
                // /profile), response.url contient la destination réelle. Sans ça, la barre
                // d'adresse afficherait l'URL cliquée alors que le contenu affiché est celui
                // d'une tout autre page — trompeur et source de confusion.
                const finalUrl = response.url || url;
                if (push) {
                    history.pushState({}, '', finalUrl);
                } else if (finalUrl !== url) {
                    history.replaceState({}, '', finalUrl);
                }

                this.executePageScripts(doc);
                this.initializeNewContent(main);
                window.dispatchEvent(new CustomEvent('pjax:loaded', { detail: { url: finalUrl } }));
            } catch (error) {
                console.error('PJAX error:', error);
                window.location.href = url;
            } finally {
                this.loading = false;
            }
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
