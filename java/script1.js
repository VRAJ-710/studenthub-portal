/* StudentHub interactive features
   This file is written with simple DOM and event-listener examples. */
document.addEventListener('DOMContentLoaded', function () {
  addStyles();
  addThemeButton();
  addHamburgerMenu();
  addNotification();
  addSlider();
  addFaq();
  addModal();
  loadSavedTheme();
});

function addStyles() {
  var style = document.createElement('style');
  style.textContent = `
    .interactive-tools { padding: 12px 25px; display: flex; gap: 10px; flex-wrap: wrap; }
    .interactive-tools button, .studenthub-button { margin: 0; }
    .theme-dark { background-color: #222; color: #eeeeee; }
    .theme-dark h2 { color: #8fc4ff; border-color: #8fc4ff; }
    .theme-dark form, .theme-dark .studenthub-box, .theme-dark .studenthub-modal-content { background-color: #333; color: #eeeeee; }
    .theme-dark input { background-color: #444; color: white; }
    .studenthub-banner { margin: 15px 25px; padding: 12px; background: #fff3cd; border: 1px solid #e0b400; color: #5c4800; }
    .studenthub-banner button { float: right; padding: 2px 8px; }
    .studenthub-slider, .studenthub-faq { margin: 20px 25px; max-width: 700px; }
    .studenthub-box { background: white; border: 1px solid #cccccc; padding: 18px; }
    .studenthub-slide { display: none; min-height: 80px; }
    .studenthub-slide.active { display: block; }
    .slider-buttons { margin-top: 12px; display: flex; gap: 8px; }
    .faq-question { width: 100%; text-align: left; margin-top: 8px; }
    .faq-answer { display: none; padding: 10px; border: 1px solid #ddd; background: white; }
    .faq-answer.open { display: block; }
    .studenthub-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); padding: 12vh 20px; z-index: 10; }
    .studenthub-modal.open { display: block; }
    .studenthub-modal-content { max-width: 450px; margin: auto; background: white; padding: 25px; border-radius: 6px; }
    .close-modal { float: right; padding: 3px 9px; }
    .menu-button { display: none; }
    @media (max-width: 600px) {
      .menu-button { display: inline-block; margin: 10px 25px; }
      nav.studenthub-menu-closed a { display: none; }
      nav.studenthub-menu-open a { display: block; text-align: left; }
    }
  `;
  document.head.appendChild(style);
}

function addThemeButton() {
  var tools = getToolsArea();
  var button = document.createElement('button');
  button.textContent = 'Light / Dark Theme';
  button.setAttribute('aria-label', 'Change light or dark theme');
  button.addEventListener('click', function () {
    document.body.classList.toggle('theme-dark');
    var theme = document.body.classList.contains('theme-dark') ? 'dark' : 'light';
    localStorage.setItem('studenthub-theme', theme);
  });
  tools.appendChild(button);
}

function loadSavedTheme() {
  if (localStorage.getItem('studenthub-theme') === 'dark') {
    document.body.classList.add('theme-dark');
  }
}

function addHamburgerMenu() {
  var nav = document.querySelector('nav');
  if (!nav) {
    nav = document.createElement('nav');
    nav.innerHTML = '<a href="index.html">Home</a><a href="pages/dashboard.html">Dashboard</a><a href="pages/contact.html">Contact</a>';
    document.body.insertBefore(nav, document.body.children[1]);
  }
  nav.classList.add('studenthub-menu-closed');
  var button = document.createElement('button');
  button.className = 'menu-button';
  button.textContent = '☰ Menu';
  button.setAttribute('aria-expanded', 'false');
  button.addEventListener('click', function () {
    var isOpen = nav.classList.toggle('studenthub-menu-open');
    nav.classList.toggle('studenthub-menu-closed', !isOpen);
    button.setAttribute('aria-expanded', isOpen);
  });
  nav.parentNode.insertBefore(button, nav);
}

function addNotification() {
  var banner = document.createElement('div');
  banner.className = 'studenthub-banner';
  banner.setAttribute('role', 'status');
  banner.innerHTML = '<strong>Notice:</strong> New assignment results are available.';
  var close = document.createElement('button');
  close.textContent = 'X';
  close.setAttribute('aria-label', 'Close notification');
  close.addEventListener('click', function () {
    banner.remove();
  });
  banner.appendChild(close);
  document.body.insertBefore(banner, document.body.firstChild);
}

function addSlider() {
  var section = document.createElement('section');
  section.className = 'studenthub-slider';
  section.innerHTML = '<h2>Student Tips</h2><div class="studenthub-box"><p class="studenthub-slide active">Tip 1: Check your assignments before their due dates.</p><p class="studenthub-slide">Tip 2: Keep your profile information up to date.</p><p class="studenthub-slide">Tip 3: Contact Student Services when you need help.</p><div class="slider-buttons"><button id="previous-slide">Previous</button><button id="next-slide">Next</button></div></div>';
  document.body.appendChild(section);

  var slides = section.querySelectorAll('.studenthub-slide');
  var currentSlide = 0;
  section.querySelector('#next-slide').addEventListener('click', function () {
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
  });
  section.querySelector('#previous-slide').addEventListener('click', function () {
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
  });
}

function addFaq() {
  var section = document.createElement('section');
  section.className = 'studenthub-faq';
  section.innerHTML = '<h2>Frequently Asked Questions</h2><button class="faq-question" aria-expanded="false">How do I view my results?</button><div class="faq-answer">Open the Result page from the navigation menu.</div><button class="faq-question" aria-expanded="false">Who can help with login problems?</button><div class="faq-answer">Contact Student Services for help with your account.</div>';
  document.body.appendChild(section);

  section.querySelectorAll('.faq-question').forEach(function (question) {
    question.addEventListener('click', function () {
      var answer = question.nextElementSibling;
      var isOpen = answer.classList.toggle('open');
      question.setAttribute('aria-expanded', isOpen);
    });
  });
}

function addModal() {
  var tools = getToolsArea();
  var openButton = document.createElement('button');
  openButton.textContent = 'Open Welcome Popup';
  openButton.className = 'studenthub-button';
  tools.appendChild(openButton);

  var modal = document.createElement('div');
  modal.className = 'studenthub-modal';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.setAttribute('aria-labelledby', 'welcome-title');
  modal.innerHTML = '<div class="studenthub-modal-content"><button class="close-modal" aria-label="Close popup">X</button><h2 id="welcome-title">Welcome to StudentHub</h2><p>Use the portal menu to find your student information.</p></div>';
  document.body.appendChild(modal);

  openButton.addEventListener('click', function () {
    modal.classList.add('open');
    modal.querySelector('.close-modal').focus();
  });
  modal.querySelector('.close-modal').addEventListener('click', function () {
    modal.classList.remove('open');
    openButton.focus();
  });
  modal.addEventListener('click', function (event) {
    if (event.target === modal) {
      modal.classList.remove('open');
    }
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      modal.classList.remove('open');
    }
  });
}

function getToolsArea() {
  var tools = document.querySelector('.interactive-tools');
  if (!tools) {
    tools = document.createElement('div');
    tools.className = 'interactive-tools';
    document.body.insertBefore(tools, document.body.children[1]);
  }
  return tools;
}
