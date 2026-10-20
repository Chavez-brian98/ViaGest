const sidebar = document.getElementById('appSidebar');

if (sidebar) {
    const backdrop = document.getElementById('sidebarBackdrop');
    const collapseBtn = document.getElementById('sidebarToggle');
    const openBtn = document.getElementById('sidebarOpenBtn');
    const storageKey = 'viagest.sidebar.collapsed';

    const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

    if (localStorage.getItem(storageKey) === '1') {
        sidebar.classList.add('is-collapsed');
    }

    const openDrawer = () => {
        sidebar.classList.add('is-open');
        backdrop?.classList.add('is-visible');
    };

    const closeDrawer = () => {
        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
    };

    collapseBtn?.addEventListener('click', () => {
        const collapsed = sidebar.classList.toggle('is-collapsed');
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
    });

    openBtn?.addEventListener('click', openDrawer);
    backdrop?.addEventListener('click', closeDrawer);

    sidebar.querySelectorAll('nav a').forEach((link) => {
        link.addEventListener('click', () => {
            if (!isDesktop()) {
                closeDrawer();
            }
        });
    });

    window.addEventListener('resize', () => {
        if (isDesktop()) {
            closeDrawer();
        }
    });
}
