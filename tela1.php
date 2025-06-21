<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Three.js Obracający Box</title>
  <link href="style.css" rel="stylesheet">
  <style>
   


  </style>
</head>
<body>

<div id="menu-placeholder"></div>
<div id="errorBox" style="color:red; padding:10px;"></div>


<div id="controls">
  <label>rotationSpeed</label>
  <button onclick="changeSpeed(0.001)">🔼 Szybciej</button>
  <button onclick="changeSpeed(-0.001)">🔽 Wolniej</button>
  <span style="margin-left: 10px;">🔁 Prędkość: <span id="speed-display">0.005</span></span>
</div>

<div style="text-align:center; margin-bottom: 20px;">
  <select id="folderSelect">
    <option value="ALL">📁 Wszystkie foldery</option>
  </select>
  <button onclick="loadSelectedFolder()">📥 Wczytaj wybrane</button>
</div>

<div style="text-align:center; margin-bottom: 20px;">
  <label for="bgColorPicker">defaultBackgroundColor: </label>
  <input type="color" id="bgColorPicker" value="#111111" />
  <input type="text" id="bgColorHex" value="#111111" maxlength="7" style="width:80px; margin-left:10px;" />
</div>

<div id="scale-controls">
  <label for="uniformScale">uniformBoxScale (skala):</label><br />
  <input type="range" id="uniformScale" min="0.5" max="3" step="0.1" value="1" />
  <span id="scaleDisplay">1.0</span>x
</div>

<div style="text-align:center; margin: 20px;">
  <label>visiblePhotoCount: </label>
  <input type="range" id="photoCountSlider" min="1" max="6" value="4" />
  <span id="photoCountDisplay">4</span>

  <br><br>

  <label>Odstęp między zdjęciami:</label>
  <input type="range" id="spacingSlider" min="0" max="3" step="0.1" value="1.5" />
  <span id="spacingDisplay">1.5</span>
</div> 

<div id="three-container" style="height: 400px;
	width: 100%;
	display: block;"></div>

<script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>

<script>

const baseDir = 'foto'; 
const basePath = baseDir + '/';
const defaultFolder = 'zwierzeta';
const defaultSpacing = 1;
const visiblePhotoCount = 4;
const pauseOnHover = true;
const originalSpeed = 0.01;
let backgroundColor = '#BF7373';
let rotationSpeed = 0.005;


let marqueeSpeed = originalSpeed;
let scene, camera, renderer, cube;
let textures = [];
let imageIndex = 0;
let imageList = [];
let refreshInterval;
let uniformBoxScale = 1;
let planes = [];
let galleryGroup = new THREE.Group();
let ambientLightAdded = false;
let raycaster = new THREE.Raycaster();
let mouse = new THREE.Vector2();




// Inicjalizacja Three.js
function initThree() {
  const container = document.getElementById('three-container');
  scene = new THREE.Scene();
  scene.background = new THREE.Color(backgroundColor);

  const width = container.clientWidth;
  const height = container.clientHeight;
  const aspect = width / height;

  camera = new THREE.PerspectiveCamera(75, aspect, 0.1, 1000);
  camera.position.z = 2.5;  // <--- stałe przybliżenie, działa w każdej wysokości!

  renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(width, height);
  container.appendChild(renderer.domElement);

  const light = new THREE.PointLight(0xffffff, 1, 100);
  light.position.set(10, 10, 10);
  scene.add(light);

  renderer.domElement.addEventListener('click', handleRaycastClick);
  renderer.domElement.addEventListener('mousemove', handleMouseMove);
if (pauseOnHover) {
  renderer.domElement.addEventListener('mouseenter', () => marqueeSpeed = 0);
  renderer.domElement.addEventListener('mouseleave', () => marqueeSpeed = originalSpeed);
}


  animate();
  updateBoxScale();
}

function getSpacing() {
  const slider = document.getElementById('spacingSlider');
  if (slider) {
    return parseFloat(slider.value);
  } else {
    return defaultSpacing;
  }
}

function wrapGalleryLoop() {
  const spacing = getSpacing();
  const boxWidth = 1 * uniformBoxScale;
  const totalWidth = planes.length * (boxWidth + spacing);

  if (galleryGroup.position.x > totalWidth / 2) {
    galleryGroup.position.x = -totalWidth / 2;
  }
}

function getPhotoCount() {
  const slider = document.getElementById('photoCountSlider');
  if (slider) {
    return parseInt(slider.value);
  } else {
    return visiblePhotoCount;
  }
}

function updateVisibility() {
  const photoCount = getPhotoCount();
  const spacing = getSpacing();
  const boxWidth = 1 * uniformBoxScale;
  const viewWidth = getPhotoCount() * (boxWidth + spacing);

  const fadeZone = boxWidth * 2; // szerokość strefy przejścia
  const leftEdge = -viewWidth / 2;
  const rightEdge = viewWidth / 2;

  planes.forEach(plane => {
    const worldPos = new THREE.Vector3();
    plane.getWorldPosition(worldPos);
    const x = worldPos.x;

    let opacity = 1;

    if (x < leftEdge - fadeZone || x > rightEdge + fadeZone) {
      opacity = 0;
    } else if (x < leftEdge) {
      opacity = (x - (leftEdge - fadeZone)) / fadeZone;
    } else if (x > rightEdge) {
      opacity = ((rightEdge + fadeZone) - x) / fadeZone;
    }

    plane.material.opacity = THREE.MathUtils.clamp(opacity, 0, 1);
  });
}


function updateDynamicRotation() {
  const maxAngle = 40; // maksymalny kąt odchylenia
  const viewRadius = 6; // jak daleko od środka obracać

  planes.forEach(plane => {
    const worldPos = new THREE.Vector3();
    plane.getWorldPosition(worldPos);
    const x = worldPos.x;

    const normalized = THREE.MathUtils.clamp(x / viewRadius, -1, 1);
    const angle = normalized * maxAngle;
    plane.rotation.y = THREE.MathUtils.degToRad(angle);
  });
}


function animate() {
	 wrapGalleryLoop();
  updateVisibility();	 
   updateDynamicRotation();
   renderer.render(scene, camera);
  requestAnimationFrame(animate);
galleryGroup.position.x += marqueeSpeed;


  
}





// Obsługa kliknięcia myszy (Raycaster)
function handleRaycastClick(event) {
  const rect = renderer.domElement.getBoundingClientRect();
  mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
  mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);
  const intersects = raycaster.intersectObjects(planes);

  if (intersects.length > 0) {
    const tex = intersects[0].object.material.map;
    const url = tex?.image?.currentSrc || tex?.image?.src;
    if (url) window.open(url, '_blank');
  }
}

// Obsługa podświetlania ściany
let highlightedFace = null;
let originalColor = null;

function handleMouseMove(event) {
  const rect = renderer.domElement.getBoundingClientRect();
  mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
  mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);
  const intersects = raycaster.intersectObject(cube || new THREE.Mesh());

  if (intersects.length > 0) {
    const faceIndex = intersects[0].face.materialIndex;
    const material = cube.material[faceIndex];
    if (highlightedFace !== faceIndex) {
      if (highlightedFace !== null && originalColor) {
        cube.material[highlightedFace].color.set(originalColor);
      }
      highlightedFace = faceIndex;
      originalColor = material.color.clone();
      material.color.multiplyScalar(1.3);
    }
  } else {
    if (highlightedFace !== null && originalColor) {
      cube.material[highlightedFace].color.set(originalColor);
    }
    highlightedFace = null;
    originalColor = null;
  }
}

// Aktualizacja skali
function updateBoxScale() {
  planes.forEach(plane => {
    plane.scale.set(uniformBoxScale, uniformBoxScale, 1);
  });
  centerPlanes();
}

function centerPlanes() {
  const spacing = getSpacing();
  const width = 1;
  const totalWidth = planes.length * width + (planes.length - 1) * spacing;
  const startX = -totalWidth / 2 + width / 2;

  planes.forEach((plane, i) => {
    plane.position.x = startX + i * (width + spacing);
    plane.updateMatrixWorld();
  });
}

function centerGalleryGroup() {
const spacing = parseFloat(document.getElementById('spacingSlider')?.value) || defaultSpacing;
  const width = 1 * uniformBoxScale;
  const totalWidth = planes.length * width + (planes.length - 1) * spacing * uniformBoxScale;
  galleryGroup.position.x = -totalWidth / 2 + width / 2;
}

function changeSpeed(delta) {
  marqueeSpeed = Math.max(0.001, marqueeSpeed + delta);
  document.getElementById('speed-display').textContent = marqueeSpeed.toFixed(3);
}


fetch(`get-folders.php?baseDir=${baseDir}`)
  .then(response => response.json())
  .then(data => {
    window.allFolderData = data;

    const select = document.getElementById('folderSelect');

    if (select) {
      while (select.options.length > 1) {
        select.remove(1);
      }

      data.forEach(folder => {
        const opt = document.createElement('option');
        opt.value = folder.name;
        opt.textContent = folder.name;
        select.appendChild(opt);
      });

      // Ustaw domyślnie wybrany folder
      select.value = defaultFolder;
    } else {
      console.warn('Brak elementu #folderSelect — pomijam uzupełnianie selecta');
    }

    loadSelectedFolder();
  })
  .catch(err => console.error('Błąd fetch:', err));


function loadSelectedFolder() {
  const folder = defaultFolder;
  imageList = [];

  if (!window.allFolderData || window.allFolderData.length === 0) {
    showError('Brak danych folderów. Nie można wczytać obrazów.');
    return;
  }

  if (folder === 'ALL') {
    window.allFolderData.forEach(f => {
      f.images.forEach(img => imageList.push(`${basePath}${f.name}/${img}`));
    });
  } else {
    const selectedFolder = window.allFolderData.find(f => f.name === folder);
    if (!selectedFolder) {
      showError(`Folder "${folder}" nie istnieje.`);
      return;
    }

    selectedFolder.images.forEach(img => imageList.push(`${basePath}${folder}/${img}`));
  }

  shuffleArray(imageList);

  const count = parseInt(document.getElementById('photoCountSlider')?.value) || imageList.length;
  const selectedImages = imageList;

  preloadAllTextures(selectedImages).then(loaded => {
    createPlanes(loaded);
  });
}

function createPlanes(textures) {
  planes.forEach(p => galleryGroup.remove(p));
  planes = [];

  if (!ambientLightAdded) {
    scene.add(new THREE.AmbientLight(0xffffff, 0.6));
    const dirLight = new THREE.DirectionalLight(0xffffff, 0.6);
    dirLight.position.set(3, 3, 5);
    scene.add(dirLight);
    ambientLightAdded = true;
  }

  const spacing = parseFloat(document.getElementById('spacingSlider')?.value) || defaultSpacing;

  textures.forEach((tex, i) => {
    const geo = new THREE.PlaneGeometry(1, 1);
const mat = new THREE.MeshStandardMaterial({
  map: tex,
  roughness: 1,
  metalness: 0,
  transparent: true,
  opacity: 1
});

    const plane = new THREE.Mesh(geo, mat);
const middle = (textures.length - 1) / 2;
const maxAngle = 40; // stopnie zakrzywienia

plane.position.x = i * (1 + spacing);

const angle = ((i - middle) / middle) * maxAngle;
plane.rotation.y = THREE.MathUtils.degToRad(angle);

    galleryGroup.add(plane);
    planes.push(plane);
  });

  updateBoxScale();
  centerGalleryGroup();

  if (!scene.children.includes(galleryGroup)) {
    scene.add(galleryGroup);
  }
}

function preloadAllTextures(urls) {
  const loader = new THREE.TextureLoader();
  return Promise.all(urls.map(url => new Promise(resolve => {
    loader.load(url, resolve, undefined, () => resolve(null));
  }))).then(textures => textures.filter(Boolean));
}

function shuffleArray(array) {
  for (let i = array.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [array[i], array[j]] = [array[j], array[i]];
  }
}



// Obsługa nieobecnych elementów UI
const scaleInput = document.getElementById('uniformScale');
if (scaleInput) {
  scaleInput.addEventListener('input', (e) => {
    uniformBoxScale = parseFloat(e.target.value);
    const scaleDisplay = document.getElementById('scaleDisplay');
    if (scaleDisplay) scaleDisplay.textContent = uniformBoxScale.toFixed(1);
    updateBoxScale();
  });
}

const bgInput = document.getElementById('bgColorPicker');
if (bgInput) {
  bgInput.addEventListener('input', (e) => {
    backgroundColor = e.target.value;
    if (scene) scene.background = new THREE.Color(backgroundColor);
  });
}



const photoCountSlider = document.getElementById('photoCountSlider');
if (photoCountSlider) {
  photoCountSlider.addEventListener('input', e => {
    const val = parseInt(e.target.value);
    const photoCountDisplay = document.getElementById('photoCountDisplay');
    if (photoCountDisplay) {
      photoCountDisplay.textContent = val;
    }
  });
}


const spacingSlider = document.getElementById('spacingSlider');
if (spacingSlider) {
  spacingSlider.addEventListener('input', e => {
    const val = parseFloat(e.target.value);
    const spacingDisplay = document.getElementById('spacingDisplay');
    if (spacingDisplay) {
      spacingDisplay.textContent = val;
    }
    if (planes.length) {
      centerPlanes();           // ← ustawia pozycje zdjęć na nowo
      centerGalleryGroup();     // ← przelicza pozycję całej grupy
    }
  });
}


// Start aplikacji
initThree();
</script>



<script src="main.js" ></script>

</body>
</html>
