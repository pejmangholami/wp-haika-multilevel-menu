/* فایل haika-script.js */
jQuery(document).ready(function($) {
    
    // دریافت اطلاعات دکمه
    function getButtonInfo($btn) {
        var text = $btn.data('text') || 'منو';
        var icon = $btn.data('icon') || '';
        var iconSize = $btn.data('icon-size') || '20';
        
        return {
            text: text,
            icon: icon,
            iconSize: iconSize
        };
    }
    
    // ساخت محتوای دکمه
    function createButtonContent(info, isOpen) {
        var content = '';
        var displayText = info.text;
        var iconHtml = '';
        
        // اگر آیکون وجود دارد
        if (info.icon) {
            iconHtml = '<img src="' + info.icon + '" alt="منو" class="menu-icon">';
            
            // اگر متن خالی است، فقط آیکون نمایش داده شود
            if (!displayText || displayText.trim() === '' || displayText === 'منو') {
                content = iconHtml;
            } else {
                // اگر متن وجود دارد، هم آیکون و هم متن
                if (isOpen) {
                    content = '✕ بستن';
                } else {
                    content = iconHtml + ' ' + displayText;
                }
            }
        } else {
            // اگر آیکون نداریم، متن را نمایش بده
            if (isOpen) {
                content = '✕ بستن';
            } else {
                content = displayText || 'منو';
            }
        }
        
        return content;
    }
    
    // کلیک روی دکمه منو
    $('.haika-menu-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $menu = $btn.siblings('.haika-navbar');
        var info = getButtonInfo($btn);
        var isOpen = $menu.hasClass('active');
        
        // تغییر وضعیت منو
        $menu.toggleClass('active');
        $btn.toggleClass('menu-open');
        
        // تغییر محتوای دکمه
        var newContent = createButtonContent(info, !isOpen);
        $btn.html(newContent);
        
        // اضافه کردن اتریبوت اندازه آیکون
        if (info.iconSize) {
            $btn.attr('data-icon-size', info.iconSize);
        }
    });
    
    // بستن منو با کلیک خارج از آن
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.haika-menu-container').length) {
            $('.haika-navbar').removeClass('active');
            
            $('.haika-menu-btn').each(function() {
                var $btn = $(this);
                var info = getButtonInfo($btn);
                
                $btn.removeClass('menu-open');
                $btn.html(createButtonContent(info, false));
                
                if (info.iconSize) {
                    $btn.attr('data-icon-size', info.iconSize);
                }
            });
        }
    });
    
    // بستن منو با کلید Escape
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) {
            $('.haika-navbar').removeClass('active');
            
            $('.haika-menu-btn').each(function() {
                var $btn = $(this);
                var info = getButtonInfo($btn);
                
                $btn.removeClass('menu-open');
                $btn.html(createButtonContent(info, false));
                
                if (info.iconSize) {
                    $btn.attr('data-icon-size', info.iconSize);
                }
            });
        }
    });
    
    // تنظیم اندازه آیکون بر اساس تنظیمات
    function applyIconSize() {
        $('.haika-menu-btn').each(function() {
            var $btn = $(this);
            var iconSize = $btn.data('icon-size') || '20';
            $btn.attr('data-icon-size', iconSize);
        });
    }
    
    // اعمال تنظیمات در بارگذاری
    applyIconSize();
    
});