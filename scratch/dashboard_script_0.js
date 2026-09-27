
(function() {
    try {
        localStorage.removeItem('st_user_banner_url');
        localStorage.removeItem('st_saved_tiktok_video');
        localStorage.removeItem('st_user_banner_ts');
        localStorage.removeItem('st_saved_banner_pos');
        localStorage.removeItem('st_saved_banner_fit');
        localStorage.removeItem('st_saved_video_fit');

        var svId = "";
        var b = localStorage.getItem('st_user_banner_url_' + svId);
        if (!b) {
            var saved = localStorage.getItem('sf_banner_gallery_' + svId);
            if (saved) {
                try {
                    var items = JSON.parse(saved);
                    if (Array.isArray(items) && items.length > 0) {
                        b = items[items.length - 1];
                    }
                } catch(err) {}
            }
        }
        if (b && typeof b === 'string') {
            b = b.replace(/^["']+|["']+$|\\/g, '').trim();
            if (b && b.indexOf('http') !== 0 && b.indexOf('data:') !== 0 && b.indexOf('/') !== 0) {
                b = '/tkb/assets/img/banners/' + b;
            }
            if (b && b.indexOf('minecraft_hero.png') === -1 && b.indexOf('ponyo_banner.gif') === -1 && b.indexOf('1785338518') === -1 && b.indexOf('1785302601') === -1) {
                window.__PRELOADED_BANNER__ = b;
            }
        }
    } catch(e) {}
})();
