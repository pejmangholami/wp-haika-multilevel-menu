jQuery(document).ready(function($) {
    const menuToggle = $('.haika-menu-btn');
    const sidebar = $('#sidebar');
    let isMenuOpen = false;

    if (menuToggle.length && sidebar.length) {
        menuToggle.on('click', (e) => {
            e.preventDefault();
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
        const toggle = $(e.currentTarget);
        const parent = toggle.closest('.level3-parent');
        const submenu = parent.find('.level3-submenu');
        
        if (submenu.length) {
            submenu.toggleClass('hidden');
        }
    });

    // Close menu when clicking outside of it
    $(document).on('click', function(e) {
        if (isMenuOpen && !$(e.target).closest('.haika-menu-container').length) {
            isMenuOpen = false;
            sidebar.addClass('-translate-x-full').removeClass('translate-x-0');
        }
    });
});
