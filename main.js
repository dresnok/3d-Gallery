// document.addEventListener('click', (e) => {
  // if (!menu.contains(e.target) && !toggleBtn.contains(e.target) && menuVisible) {
    // menuVisible = false;
    // menu.style.display = 'none';
    // toggleBtn.textContent = '☰';
  // }
// });
document.addEventListener("DOMContentLoaded", () => {
  // 1. Załaduj menu z pliku HTML
  fetch('menu.html')
    .then(response => response.text())
    .then(html => {
      document.getElementById('menu-placeholder').innerHTML = html;
    })
    .then(() => {
      // Po załadowaniu menu zainicjalizuj eventy menu
      initMenu();
    })
    .catch(err => console.error('Błąd wczytywania menu:', err));

  // 2. Obsługa kolorów hex (jeśli elementy istnieją)
  const colorInput = document.getElementById('bgColorPicker');
  const hexInput = document.getElementById('bgColorHex');

  if (colorInput && hexInput) {
    colorInput.addEventListener('input', () => {
      hexInput.value = colorInput.value.toUpperCase();
    });

    hexInput.addEventListener('input', () => {
      const val = hexInput.value;
      if (/^#([0-9A-F]{6})$/i.test(val)) {
        colorInput.value = val;
      }
    });
  }

  // 3. Tooltipy - globalne funkcje
let hideTimer;

window.showTooltip = function () {
  clearTimeout(hideTimer);
  const tooltip = document.getElementById('tooltip');
  if (!tooltip) return;
  tooltip.classList.add('show');
};

window.startHideTooltipTimer = function () {
  const tooltip = document.getElementById('tooltip');
  if (!tooltip) return;
  hideTimer = setTimeout(() => {
    tooltip.classList.remove('show');
  }, 1000);
};



  
  
});

// Funkcja inicjująca menu - wywoływana po załadowaniu menu.html
function initMenu() {
  const toggleBtn = document.getElementById('menu-toggle');
  const menu = document.getElementById('menu');

  if (!toggleBtn || !menu) return;

  let menuVisible = localStorage.getItem('menuVisible');
  if (menuVisible === null) {
    menuVisible = true;
    localStorage.setItem('menuVisible', true);
  } else {
    menuVisible = menuVisible === 'true';
  }

  menu.style.display = menuVisible ? 'block' : 'none';
  toggleBtn.textContent = menuVisible ? '✖' : '☰';

  toggleBtn.addEventListener('click', () => {
    menuVisible = !menuVisible;
    menu.style.display = menuVisible ? 'block' : 'none';
    toggleBtn.textContent = menuVisible ? '✖' : '☰';
    localStorage.setItem('menuVisible', menuVisible);
  });
}
