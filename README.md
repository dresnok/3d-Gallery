## Live Demo

[Zobacz demo](http://company12.atwebpages.com/?next=awesome-3dGallery)

# Three.js Gallery Viewer

## Struktura projektu

- **Pliki główne** (`davanti.php`, `scatola1.php`, `get-folders.php` itp.) znajdują się w **katalogu głównym** i zawierają najważniejszy kod aplikacji – w tym logikę tworzenia i animowania galerii 3D przy użyciu Three.js.

- **Katalogi pomocnicze** zawierają dodatkowe skrypty niezbędne do działania edytora oraz interfejsu użytkownika.

## Aktualizacja
- sprawdzono pod kątem błędów (06.2025)

## Wymagania

Plik główny, plik php, foldery i zdjęcia

## Konfiguracja

Centralną częścią logiki jest obiekt konfiguracyjny:

```js
const CONFIG = {
  defaultFolder: 'zwierzeta',
  basePath: 'foto/',
  defaultRotationSpeed: 0.012,
  defaultBackgroundColor: '#B18686',
  defaultBoxScale: 2.0,
  visiblePhotoCount: 4,
  defaultSpacing: 1.5,
  skewAngle: -15,
  materialType: 'standard',
  lights: {
    ambientLight: { color: 0xffffff, intensity: 0.8 },
    directionalLight: { color: 0xffffff, intensity: 0.5, position: [2, 4, 3] },
    pointLight: { color: 0xfff0cc, intensity: 1.5, distance: 150, position: [5, 5, 5] }
  }
};
```

Zawiera on wszystkie kluczowe parametry konfiguracji galerii, takie jak:

- domyślny folder ze zdjęciami (`defaultFolder`)
- tło sceny, szybkość animacji, skala obiektów
- rodzaj materiałów (`MeshStandardMaterial` lub `MeshBasicMaterial`)
- ustawienia oświetlenia: ambient, directional, point light

## Jak działa galeria?

Galeria renderuje przesuwające się obrazy w przestrzeni 3D jako pochylone płaszczyzny (`PlaneGeometry`) w formie ciągłego karuzelowego przesuwu. Obrazy są wczytywane dynamicznie z wybranego folderu i wyświetlane z efektem zanikania przy krawędziach. Można kliknąć zdjęcie, by otworzyć je w nowej karcie.

## Przykład osadzenia

```html
<body>
  <div id="three-container" style="height: 200px; width: 100%; display: block;"></div>

  <script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>
  <script>
    // kod inicjalizujący scenę i galerię...
  </script>
</body>
```

## Licencja

Projekt testowo-edukacyjny – możesz używać, modyfikować i rozszerzać dowolnie.

---

## Strona domowa

Webmaster: [asperion](http://asperion24.eu/)

Data utworzenia: 2025-06-20