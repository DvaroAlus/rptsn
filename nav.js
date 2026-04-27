(function () {
  const page = document.body.dataset.page || '';
  function a(p) { return page === p ? 'class="active"' : ''; }

  const navHTML = `
<nav id="navbar">
  <a href="index.html" class="nav-logo">
    <div class="nav-logo-mark"><img src="img/logo.png" alt="Тосненское Райпо"></div>
    <div>
      <div class="nav-logo-name">Тосненское Райпо</div>
      <div class="nav-logo-tagline">Потребительское общество · 1946</div>
    </div>
  </a>
  <ul class="nav-links">
    <li><a href="index.html"    ${a('home')}>Главная</a></li>
    <li><a href="about.html"    ${a('about')}>Об организации</a></li>
    <li><a href="stores.html"   ${a('stores')}>Торговая сеть</a></li>
    <li><a href="news.html"     ${a('news')}>Новости</a></li>
    <li><a href="contacts.html" class="nav-cta">Контакты</a></li>
  </ul>
  <button class="burger" id="burger" aria-label="Меню" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
</nav>
<div class="mobile-menu" id="mobile-menu">
  <nav class="mobile-nav">
    <a href="index.html"    ${page==='home'?'class="active"':''}>Главная</a>
    <a href="about.html"    ${page==='about'?'class="active"':''}>Об организации</a>
    <a href="stores.html"   ${page==='stores'?'class="active"':''}>Торговая сеть</a>
    <a href="news.html"     ${page==='news'?'class="active"':''}>Новости</a>
    <a href="contacts.html" class="mobile-cta">Контакты</a>
  </nav>
  <div class="mobile-footer-info">
    <div>+7 (813-61) 2-00-00</div>
    <div>info@tosno-raipo.ru</div>
  </div>
</div>
<div class="mobile-overlay" id="mobile-overlay"></div>`;

  const footerHTML = `
<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo-mark"><img src="img/logo.png" alt="Тосненское Райпо"></div>
      <div class="footer-name">Тосненское Райпо</div>
      <div class="footer-sub">Потребительское общество · с 1946 года</div>
      <p class="footer-desc">Ленинградская область, Тосненский район. Продовольственная и промтоварная торговля, аптечная деятельность.</p>
    </div>
    <div class="footer-col">
      <h4>Разделы</h4>
      <a href="index.html">Главная</a>
      <a href="about.html">Об организации</a>
      <a href="stores.html">Торговая сеть</a>
      <a href="news.html">Новости</a>
      <a href="contacts.html">Контакты</a>
    </div>
    <div class="footer-col">
      <h4>Деятельность</h4>
      <span>Продовольственная торговля</span>
      <span>Промтоварная торговля</span>
      <span>Аптечная деятельность</span>
      <span>Общественное питание</span>
      <span>Ветеринарная клиника</span>
      <span>Зоомаркет</span>
    </div>
    <div class="footer-col">
      <h4>Контакты</h4>
      <span>г. Тосно, ул. Советская, 7</span>
      <span>+7 (813-61) 2-00-00</span>
      <span>info@tosno-raipo.ru</span>
      <span>ИНН 4716000001</span>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2025 Тосненское районное потребительское общество</span>
    <span>187000, Ленинградская обл., г. Тосно</span>
  </div>
</footer>`;

  document.body.insertAdjacentHTML('afterbegin', navHTML);
  document.body.insertAdjacentHTML('beforeend', footerHTML);

  /* Бургер */
  const burger = document.getElementById('burger');
  const menu   = document.getElementById('mobile-menu');
  const overlay= document.getElementById('mobile-overlay');
  const open  = () => { burger.classList.add('open'); burger.setAttribute('aria-expanded','true');  menu.classList.add('open');  overlay.classList.add('open');  document.body.style.overflow='hidden'; };
  const close = () => { burger.classList.remove('open'); burger.setAttribute('aria-expanded','false'); menu.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow=''; };
  burger.addEventListener('click', () => menu.classList.contains('open') ? close() : open());
  overlay.addEventListener('click', close);
  menu.querySelectorAll('a').forEach(a => a.addEventListener('click', close));

  /* Тень при скролле */
  window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 10);
  }, { passive: true });

  /* Scroll-reveal */
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => io.observe(el));
})();
