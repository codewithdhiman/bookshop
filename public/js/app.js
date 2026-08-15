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
});
