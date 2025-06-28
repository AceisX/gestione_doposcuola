<!-- Add FontAwesome CSS link for icons -->
<link rel="stylesheet" href="/webapp/assets/fontawesome/css/all.min.css">

<style>
/* Container for the floating menu */
.floating-menu {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1500;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
}

/* Main burger button */
.menu-button {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.6);
    border: none;
    cursor: pointer;
    color: white;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    pointer-events: auto;
}

.menu-button:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transform: scale(1.05);
}

/* Animazione per l'icona del burger menu */
.menu-button i {
    transition: transform 0.3s ease;
}

.floating-menu.active .menu-button i {
    transform: rotate(90deg);
}

/* Container for menu items */
.menu-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.3s ease;
    pointer-events: none;
}

/* Show menu items when active */
.floating-menu.active .menu-items {
    opacity: 1;
    transform: translateX(0);
    pointer-events: auto;
}

/* Menu item buttons */
.menu-item {
    width: 50px;
    height: 50px;
    background: #ffffff;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 22px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

/* Hover effect for menu items */
.menu-item:hover {
    background: #0ea5e9;
    color: white;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.6);
    transform: scale(1.05);
}

/* Logout button specific style */
.menu-item.logout {
    background: #ef4444;
    color: white;
    width: 40px;
    height: 40px;
    font-size: 18px;
}

.menu-item.logout:hover {
    background: #dc2626;
}

/* Tooltip text */
.menu-item[title] {
    position: relative;
}

.menu-item[title]::after {
    content: attr(title);
    position: absolute;
    left: 120%;
    top: 50%;
    transform: translateY(-50%) translateX(-10px);
    background: #0ea5e9;
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    white-space: nowrap;
    font-size: 0.875rem;
    font-weight: 500;
    pointer-events: none;
    opacity: 0;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
}

.menu-item[title]:hover::after {
    opacity: 1;
    transform: translateY(-50%) translateX(0);
}

.menu-item.logout[title]::after {
    background: #ef4444;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
}
</style>

<div class="floating-menu" id="floating-menu">
    <button class="menu-button" id="menu-button" aria-label="Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="menu-items">
        <a href="/webapp/index.php" class="menu-item" title="Home">
            <i class="fa-solid fa-house"></i>
        </a>
        <a href="/webapp/pages/gestione_lezioni.php" class="menu-item" title="Lezioni">
            <i class="fa-solid fa-chalkboard"></i>
        </a>
        <a href="/webapp/pagamenti_tutor.php" class="menu-item" title="Pagamenti">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </a>
        <a href="/webapp/dashboard.php" class="menu-item" title="Dashboard">
            <i class="fa-solid fa-chart-line"></i>
        </a>
        <a href="/webapp/inventario.php" class="menu-item" title="Inventario">
            <i class="fa-solid fa-boxes"></i>
        </a>
<?php if(isset($_SESSION['username']) && $_SESSION['username'] === 'alessandro'): ?>
        <a href="/webapp/contabilita.php" class="menu-item" title="Contabilità">
            <i class="fa-solid fa-calculator"></i>
        </a>
<?php endif; ?>
        <a href="/webapp/scripts/logout.php" class="menu-item logout" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</div>

<script>
(function() {
    function initFloatingMenu() {
        const floatingMenu = document.getElementById('floating-menu');
        const menuButton = document.getElementById('menu-button');

        if (!floatingMenu || !menuButton) {
            console.warn('Floating menu elements not found');
            return;
        }

        menuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            floatingMenu.classList.toggle('active');
        });

        // Close menu if clicking outside
        document.addEventListener('click', (e) => {
            if (!floatingMenu.contains(e.target)) {
                floatingMenu.classList.remove('active');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFloatingMenu);
    } else {
        initFloatingMenu();
    }
})();
</script>
