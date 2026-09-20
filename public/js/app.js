// public/js/app.js
document.addEventListener('DOMContentLoaded', function(){
    // Sticky navbar on scroll
    var navbar = document.querySelector('.navbar');
    if (navbar) {
        var lastScroll = 0;
        window.addEventListener('scroll', function(){
            if (window.scrollY > 30) navbar.classList.add('sticky');
            else navbar.classList.remove('sticky');
            lastScroll = window.scrollY;
        });
    }

    // Quantity steppers
    document.body.addEventListener('click', function(e){
        if (e.target.matches('.qty-step')) {
            var btn = e.target;
            var input = btn.closest('.qty-control')?.querySelector('input[type="number"]');
            if (!input) return;
            var step = parseInt(btn.dataset.step || '1', 10);
            var min = parseInt(input.min || '1', 10);
            var max = parseInt(input.max || '9999', 10);
            var val = parseInt(input.value || '0', 10) + step;
            if (val < min) val = min;
            if (val > max) val = max;
            input.value = val;
            input.dispatchEvent(new Event('change'));
        }
    });

    // Toasts
    window.toast = function(message, opts){
        opts = opts || {};
        var container = document.querySelector('.toastify');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toastify';
            document.body.appendChild(container);
        }
        var t = document.createElement('div');
        t.className = 'toast';
        t.innerText = message;
        container.appendChild(t);
        setTimeout(function(){ t.classList.add('show'); }, 10);
        setTimeout(function(){ t.remove(); }, opts.duration || 4000);
    }

    // Image preview for file inputs with data-preview attribute
    document.body.addEventListener('change', function(e){
        var input = e.target;
        if (input.type === 'file' && input.dataset.preview) {
            var file = input.files && input.files[0];
            var img = document.querySelector(input.dataset.preview);
            if (!img) return;
            if (!file) { img.src = ''; img.style.display = 'none'; return; }
            var reader = new FileReader();
            reader.onload = function(ev){ img.src = ev.target.result; img.style.display = 'block'; };
            reader.readAsDataURL(file);
        }
    });

    // YouTube preview for product forms
    document.querySelectorAll('[data-youtube-preview-target]').forEach(function(input){
        var preview = document.querySelector(input.dataset.youtubePreviewTarget);
        if (!preview) return;
        var getEmbedUrl = function(value){
            var match = String(value || '').trim().match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{11})/);
            return match ? 'https://www.youtube-nocookie.com/embed/' + match[1] + '?rel=0' : '';
        };
        var update = function(){
            var embedUrl = getEmbedUrl(input.value);
            preview.replaceChildren();
            if (!embedUrl) {
                preview.hidden = true;
                return;
            }
            var iframe = document.createElement('iframe');
            iframe.src = embedUrl;
            iframe.title = 'YouTube video preview';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframe.allowFullscreen = true;
            preview.appendChild(iframe);
            preview.hidden = false;
        };
        input.addEventListener('input', update);
        update();
    });

    // Product gallery thumbnails and zoom controls
    document.querySelectorAll('[data-product-gallery]').forEach(function(gallery){
        var main = gallery.querySelector('[data-gallery-main]');
        var slides = Array.from(gallery.querySelectorAll('[data-gallery-slide]'));
        var thumbs = Array.from(gallery.querySelectorAll('.product-viewer__thumb'));
        var level = gallery.querySelector('[data-gallery-zoom-level]');
        var current = 0;
        var zoom = 1;
        var setZoom = function(value){
            zoom = Math.max(1, Math.min(2.5, value));
            var image = slides[current] && slides[current].querySelector('[data-gallery-image]');
            if (image) {
                image.style.transform = 'scale(' + zoom + ')';
                image.classList.toggle('is-zoomed', zoom > 1);
            }
            if (level) level.textContent = Math.round(zoom * 100) + '%';
        };
        var showSlide = function(index){
            if (!slides.length) return;
            current = (index + slides.length) % slides.length;
            slides.forEach(function(slide, slideIndex){ slide.classList.toggle('is-active', slideIndex === current); });
            thumbs.forEach(function(thumb, thumbIndex){ thumb.classList.toggle('is-active', thumbIndex === current); });
            setZoom(1);
        };
        thumbs.forEach(function(button){
            button.addEventListener('click', function(){
                showSlide(parseInt(button.dataset.galleryIndex || '0', 10));
            });
        });
        gallery.querySelectorAll('[data-gallery-nav]').forEach(function(button){
            button.addEventListener('click', function(){
                showSlide(current + (button.dataset.galleryNav === 'next' ? 1 : -1));
            });
        });
        gallery.querySelectorAll('[data-gallery-zoom]').forEach(function(button){
            button.addEventListener('click', function(){
                var action = button.dataset.galleryZoom;
                setZoom(action === 'in' ? zoom + .25 : action === 'out' ? zoom - .25 : 1);
            });
        });
        main.addEventListener('click', function(){ setZoom(zoom > 1 ? 1 : 2); });
        main.addEventListener('keydown', function(event){
            if (event.key === 'ArrowLeft') showSlide(current - 1);
            if (event.key === 'ArrowRight') showSlide(current + 1);
        });
        main.addEventListener('wheel', function(event){
            event.preventDefault();
            setZoom(zoom + (event.deltaY < 0 ? .25 : -.25));
        }, {passive:false});
        showSlide(0);
    });

    // SEO char counters for inputs with data-charcount
    document.querySelectorAll('[data-charcount]').forEach(function(el){
        var max = parseInt(el.getAttribute('data-charcount')||'0',10);
        var counter = document.createElement('div');
        counter.className = 'small text-muted seo-counter';
        el.parentNode.insertBefore(counter, el.nextSibling);
        var update = function(){
            var len = (el.value||'').length;
            if (max>0) counter.textContent = (max - len) + ' chars left';
            else counter.textContent = len + ' chars';
        };
        el.addEventListener('input', update);
        update();
    });

    // Navbar collapse: close on outside click, close on nav item click, and Escape
    (function(){
        var toggler = document.querySelector('.navbar-toggler');
        var collapse = document.getElementById('navmenu');
        if (!toggler || !collapse) return;

        // helper to check open state (Bootstrap adds 'show' class)
        function isOpen(){ return collapse.classList.contains('show'); }

        // Close when clicking nav links (mobile)
        collapse.addEventListener('click', function(e){
            if (e.target.closest('.nav-link')) {
                // let bootstrap handle if present, then remove show
                collapse.classList.remove('show');
            }
        });

        // Close when clicking outside
        document.addEventListener('click', function(e){
            if (!isOpen()) return;
            if (e.target.closest('.navbar')) return;
            collapse.classList.remove('show');
        });

        // Escape to close
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && isOpen()) collapse.classList.remove('show'); });
    })();

    // Admin sidebar toggle for mobile
    (function(){
        var toggle = document.getElementById('admin-menu-toggle');
        var sidebar = document.querySelector('.admin-sidebar');
        var overlay = document.querySelector('.admin-overlay');
        if (!toggle || !sidebar) return;
        function open(){ sidebar.classList.add('open'); if(overlay) overlay.classList.add('show'); document.body.style.overflow='hidden'; }
        function close(){ sidebar.classList.remove('open'); if(overlay) overlay.classList.remove('show'); document.body.style.overflow=''; }
        toggle.addEventListener('click', function(){ if (sidebar.classList.contains('open')) close(); else open(); });
        if (overlay) overlay.addEventListener('click', close);
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && sidebar.classList.contains('open')) close(); });
        // Close when nav link inside sidebar clicked (mobile)
        sidebar.addEventListener('click', function(e){ if (e.target.closest('.nav-link')) close(); });
    })();

    // Theme init and selector
    (function(){
        function applyTheme(theme){
            try{
                if (theme === 'system'){
                    localStorage.setItem('bookshop-theme','system');
                    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    theme = prefersDark ? 'midnight' : 'classic';
                } else {
                    localStorage.setItem('bookshop-theme', theme);
                }
                document.documentElement.setAttribute('data-theme', theme);
            }catch(e){console.warn(e)}
        }

        // Wire up selector buttons
        document.querySelectorAll('.theme-select').forEach(function(btn){
            btn.addEventListener('click', function(e){
                var t = btn.getAttribute('data-theme');
                applyTheme(t);
            });
        });

        // Ensure theme is applied (in case header inline script didn't run)
        try{
            var s = localStorage.getItem('bookshop-theme');
            if (!s) s = 'classic';
            applyTheme(s);
        }catch(e){}
    })();

    // Offcanvas filters (mobile)
    (function(){
        var openBtn = document.getElementById('filters-open');
        var off = document.getElementById('filters-offcanvas');
        var backdrop = document.getElementById('filters-backdrop');
        var closeBtn = document.getElementById('filters-close');
        if (!off) return;
        function open(){ off.classList.add('open'); if (backdrop) backdrop.classList.add('show'); document.body.style.overflow='hidden'; }
        function close(){ off.classList.remove('open'); if (backdrop) backdrop.classList.remove('show'); document.body.style.overflow=''; }
        if (openBtn) openBtn.addEventListener('click', open);
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (backdrop) backdrop.addEventListener('click', close);
        document.addEventListener('keydown', function(e){ if (e.key==='Escape' && off.classList.contains('open')) close(); });
    })();

    // PDP mobile sticky add-to-cart
    (function(){
        var detail = document.querySelector('.product-detail');
        var sticky = document.querySelector('.pdp-sticky');
        var addForm = document.querySelector('.product-detail form');
        if (!detail || !sticky || !addForm) return;
        function update(){
            var rect = addForm.getBoundingClientRect();
            // if the form is within viewport bottom area, hide sticky
            if (rect.bottom <= window.innerHeight && rect.top >= 0) {
                sticky.style.transform = 'translateY(110%)';
            } else {
                sticky.style.transform = '';
            }
        }
        update();
        window.addEventListener('scroll', update);
        window.addEventListener('resize', update);
        // wire sticky form submission
        sticky.querySelectorAll('button[type="submit"]').forEach(function(b){
            b.addEventListener('click', function(e){
                e.preventDefault();
                // copy inputs from sticky to real form
                var qty = sticky.querySelector('input[name="quantity"]')?.value || 1;
                var qInput = addForm.querySelector('input[name="quantity"]');
                if (qInput) qInput.value = qty;
                addForm.querySelector('button[type="submit"]').click();
            });
        });
    })();
});
