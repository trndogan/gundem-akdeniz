{{-- Sonsuz kaydırma.
     Sayfalayıcı sunucuda olduğu gibi kalır; bu parça yalnızca arayüzü değiştirir.
     JavaScript kapalıysa normal sayfa bağlantıları görünür, sayfa çalışmaya devam eder.

     $liste  : kartların bulunduğu kapsayıcının id'si
     $sayfa  : paginator örneği --}}
@if($sayfa->hasPages())
<div class="ga-daha" data-liste="{{ $liste }}" data-sonraki="{{ $sayfa->nextPageUrl() }}">
    <div class="ga-daha__yukleniyor" aria-hidden="true">
        <span></span><span></span><span></span>
    </div>
    <button type="button" class="ga-daha__dugme" hidden>Daha fazla haber</button>
    <noscript>{{ $sayfa->links() }}</noscript>
</div>

<style>
    .ga-daha { display: flex; justify-content: center; padding: 2rem 0 3rem; min-height: 72px; }
    .ga-daha__yukleniyor { display: none; gap: .45rem; }
    .ga-daha--yukleniyor .ga-daha__yukleniyor { display: flex; }
    .ga-daha__yukleniyor span { width: 9px; height: 9px; border-radius: 50%;
        background: var(--color-primary, #fe0000); opacity: .35;
        animation: ga-daha-zipla 1s infinite ease-in-out; }
    .ga-daha__yukleniyor span:nth-child(2) { animation-delay: .15s; }
    .ga-daha__yukleniyor span:nth-child(3) { animation-delay: .3s; }
    @keyframes ga-daha-zipla { 0%, 80%, 100% { opacity: .25; transform: translateY(0); }
                               40% { opacity: 1; transform: translateY(-6px); } }

    .ga-daha__dugme { padding: .7rem 1.6rem; border-radius: 999px; font-weight: 700; font-size: .9rem;
        color: #fff; background: var(--color-primary, #fe0000); border: 0; cursor: pointer; }
    .ga-daha__dugme:hover { filter: brightness(.92); }

    @media (prefers-reduced-motion: reduce) {
        .ga-daha__yukleniyor span { animation: none; }
    }
</style>

<script>
(function () {
    var kap = document.currentScript.previousElementSibling;
    while (kap && !kap.classList.contains('ga-daha')) kap = kap.previousElementSibling;
    if (!kap) return;

    var liste = document.getElementById(kap.dataset.liste);
    var dugme = kap.querySelector('.ga-daha__dugme');
    if (!liste) return;

    var yukleniyor = false;

    function yukle() {
        var url = kap.dataset.sonraki;
        if (!url || yukleniyor) return;

        yukleniyor = true;
        kap.classList.add('ga-daha--yukleniyor');
        if (dugme) dugme.hidden = true;

        fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
            .then(function (y) { if (!y.ok) throw new Error(y.status); return y.text(); })
            .then(function (html) {
                var belge = new DOMParser().parseFromString(html, 'text/html');
                var yeni = belge.getElementById(kap.dataset.liste);
                if (yeni) {
                    while (yeni.firstElementChild) liste.appendChild(yeni.firstElementChild);
                }

                // Sonraki sayfanın adresi getirilen sayfadan okunur
                var sonrakiKap = belge.querySelector('.ga-daha');
                kap.dataset.sonraki = sonrakiKap ? (sonrakiKap.dataset.sonraki || '') : '';

                if (!kap.dataset.sonraki) {
                    gozlemci.disconnect();
                    kap.remove();
                }
            })
            .catch(function () {
                // Ağ hatasında kaydırmayla tekrar denemek yerine düğme gösterilir,
                // böylece hata döngüye girmez.
                if (dugme) dugme.hidden = false;
                gozlemci.disconnect();
            })
            .finally(function () {
                yukleniyor = false;
                kap.classList.remove('ga-daha--yukleniyor');
            });
    }

    var gozlemci = new IntersectionObserver(function (girisler) {
        if (girisler[0].isIntersecting) yukle();
    }, { rootMargin: '600px 0px' });

    gozlemci.observe(kap);
    if (dugme) dugme.addEventListener('click', function () { gozlemci.observe(kap); yukle(); });
})();
</script>
@endif
