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
});
