jQuery(document).ready(function($) {
    const menuToggle = $('.haika-menu-btn');
    const sidebar = $('#sidebar');
    let isMenuOpen = false;

    if (menuToggle.length && sidebar.length) {
        menuToggle.on('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // Prevents the click from bubbling up to the document
            isMenuOpen = !isMenuOpen;
            
            if (isMenuOpen) {
                sidebar.removeClass('-translate-x-full').addClass('translate-x-0');
            } else {
                sidebar.addClass('-translate-x-full').removeClass('translate-x-0');
            }
        });
    }

    // Level 3 submenu toggle
    $(document).on('click', '.level3-toggle', function(e) {
        e.stopPropagation(); // Prevent document click handler from firing
        const toggle = $(e.currentTarget);
        const parent = toggle.closest('.level3-parent');
        const submenu = parent.find('.level3-submenu');
        
        if (submenu.length) {
            submenu.toggleClass('hidden');
        }
    });

    // Close menu when clicking outside of it
    $(document).on('click', function(e) {
        // If the menu is open and the click is not on the button or inside the sidebar
        if (isMenuOpen && !menuToggle.is(e.target) && menuToggle.has(e.target).length === 0 && !sidebar.is(e.target) && sidebar.has(e.target).length === 0) {
            isMenuOpen = false;
            sidebar.addClass('-translate-x-full').removeClass('translate-x-0');
        }
    });
});
