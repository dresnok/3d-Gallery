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
  <label for="uniformScale">defaultBoxScale:</label><br />
  <input type="range" id="uniformScale" min="0.5" max="3" step="0.1" value="1" />
  <span id="scaleDisplay">1.0</span>x
</div>

<div style="text-align:center; margin: 20px;">
  <label>visiblePhotoCount: </label>
  <input type="range" id="photoCountSlider" min="1" max="6" value="4" />
  <span id="photoCountDisplay">4</span>

  <br><br>

  <label>defaultSpacing:</label>
  <input type="range" id="spacingSlider" min="0" max="3" step="0.1" value="1.5" />
  <span id="spacingDisplay">1.5</span>
</div>


<div id="three-container" style="height: 400px;
	width: 100%;
	display: block;"></div>

<script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>
<script>

const CONFIG = {
  defaultFolder: 'zwierzeta',
  basePath: 'foto/',
  defaultRotationSpeed: 0.012,              // Domyślna prędkość przesuwania się galerii
  defaultBackgroundColor: '#B18686',        // Domyślny kolor tła sceny (ciemny szary)
  defaultBoxScale: 2.0,                     // Domyślna skala zdjęć w galerii
  visiblePhotoCount: 4,                     //
  defaultSpacing: 1.5,                      // Domyślny odstęp między zdjęciami
  skewAngle: -15,                           // Kąt obrotu płaszczyzn lub -15

  materialType: 'standard', 				// 'standard' albo 'basic'
  transparent: true,
  
lights: {
	//mozesz zakomentować jedną z tych opcji, jedna musi pozostać
ambientLight: { color: 0xffffff, intensity: 0.8 },
directionalLight: { color: 0xffffff, intensity: 0.5, position: [2, 4, 3] },
pointLight: { color: 0xfff0cc, intensity: 1.5, distance: 150, position: [5, 5, 5] },

}

};


let backgroundColor = CONFIG.defaultBackgroundColor;
let rotationSpeed = CONFIG.defaultRotationSpeed;
let uniformBoxScale = CONFIG.defaultBoxScale;
let imageList = [];
let textures = [];
let planes = [];
let galleryGroup = new THREE.Group();
let ambientLightAdded = false;
let raycaster = new THREE.Raycaster();
let mouse = new THREE.Vector2();
let scene, camera, renderer, cube;
let imageIndex = 0;
let refreshInterval;


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

 if (CONFIG.lights.ambientLight) {
  CONFIG.lights.ambientLight = new THREE.AmbientLight(
    CONFIG.lights.ambientLight.color,
    CONFIG.lights.ambientLight.intensity
  );
  scene.add(CONFIG.lights.ambientLight);
}

if (CONFIG.lights.directionalLight) {
  const dirConf = CONFIG.lights.directionalLight; // oryginalna konfiguracja
  const directionalLight = new THREE.DirectionalLight(
    dirConf.color,
    dirConf.intensity
  );
  directionalLight.position.set(...dirConf.position);
  scene.add(directionalLight);
  
  CONFIG.lights.directionalLightInstance = directionalLight; // jeśli potrzebujesz później
}

  if (CONFIG.lights.pointLight) {
	    CONFIG.lights.pointLight = new THREE.PointLight(
    CONFIG.lights.pointLight.color,
    CONFIG.lights.pointLight.intensity,
    CONFIG.lights.pointLight.distance
  );
  CONFIG.lights.pointLight.position.set(...CONFIG.lights.pointLight.position);
  scene.add(CONFIG.lights.pointLight);
  }


renderer.domElement.addEventListener('click', handleRaycastClick);


  animate();
  updateBoxScale();
}


// 👇 Umieść poza initThree()

function getSpacing() {
  const slider = document.getElementById('spacingSlider');
  if (slider) {
    return parseFloat(slider.value);
  } else {
    return CONFIG.defaultSpacing;
  }
}

function getPhotoCount() {
  const slider = document.getElementById('photoCountSlider');
  if (slider) {
    return parseInt(slider.value);
  } else {
    return CONFIG.visiblePhotoCount;
  }
}

function getBoxScale() {
  const scaleInput = document.getElementById('uniformScale');
  if (scaleInput) {
    return parseFloat(scaleInput.value);
  } else {
    return CONFIG.defaultBoxScale;
  }
}

function getRotationSpeed() {
  const speedSlider = document.getElementById('rotationSpeed');
  if (speedSlider) {
    return parseFloat(speedSlider.value);
  } else {
    return CONFIG.defaultRotationSpeed;
  }
}

function getBackgroundColor() {
  const picker = document.getElementById('bgColorPicker');
  if (picker) {
    return picker.value;
  } else {
    return CONFIG.defaultBackgroundColor;
  }
}


function updateVisibility() {
const photoCount = getPhotoCount();

  const spacing = getSpacing();

  const boxWidth = 1 * uniformBoxScale;
const viewWidth = getPhotoCount() * (boxWidth + spacing); // ✅ Poprawnie

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


function animate() {
	wrapGalleryLoop();
  requestAnimationFrame(animate);
  galleryGroup.position.x += rotationSpeed;
  updateVisibility();
  renderer.render(scene, camera);
}


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

let highlightedFace = null;
let originalColor = null;

function updateBoxScale() {
  planes.forEach(plane => {
    plane.scale.set(uniformBoxScale, uniformBoxScale, 1);
  });
  centerPlanes();
}

function centerPlanes() {
  const spacing = getSpacing();
  const width = 1 * uniformBoxScale;
  const totalWidth = planes.length * width + (planes.length - 1) * spacing * uniformBoxScale;
  const startX = -totalWidth / 2 + width / 2;

  planes.forEach((plane, i) => {
    plane.position.x = startX + i * (width + spacing * uniformBoxScale);
    plane.updateMatrixWorld();
  });
  galleryGroup.position.x = 0;
}



function centerGalleryGroup() {
const spacing = parseFloat(document.getElementById('spacingSlider')?.value) || CONFIG.defaultSpacing;
  const width = 1 * uniformBoxScale;
  const totalWidth = planes.length * width + (planes.length - 1) * spacing * uniformBoxScale;
  galleryGroup.position.x = -totalWidth / 2 + width / 2;
}

function changeSpeed(delta) {
  rotationSpeed = Math.max(0.001, rotationSpeed + delta);
  document.getElementById('speed-display').textContent = rotationSpeed.toFixed(3);
}


// Ładowanie folderów
fetch(`get-folders.php?baseDir=${CONFIG.basePath}`)
  .then(res => {
    if (!res.ok) {
      throw new Error(`Błąd ładowania folderów: ${res.status}`);
    }
    return res.json();
  })
  .then(data => {
	  console.log('Dane folderów:', data);
    if (data.error) {
      showError(`Serwer zwrócił błąd: ${data.error}`);
      return;
    }

    if (!Array.isArray(data) || data.length === 0) {
      showError('Nie znaleziono żadnych folderów lub zdjęć.');
      return;
    }

    window.allFolderData = data;
	populateFolderSelect(data); 
    loadSelectedFolder();
  })
  .catch(err => {
    showError(`Wystąpił błąd: ${err.message}`);
  });

function populateFolderSelect(folderData) {
  const select = document.getElementById('folderSelect');
  if (!select) return;

  // Czyścimy stare opcje (poza "ALL")
  select.innerHTML = '<option value="ALL">📁 Wszystkie foldery</option>';

  folderData.forEach(folder => {
    const option = document.createElement('option');
    option.value = folder.name;
    option.textContent = `📁 ${folder.name}`;
    select.appendChild(option);
  });

  // Ustaw domyślny folder jako wybrany
  select.value = CONFIG.defaultFolder || 'ALL';

  // Obsługa zmiany wyboru
  select.addEventListener('change', (e) => {
    CONFIG.defaultFolder = e.target.value;
  });
}


function loadSelectedFolder() {
  const folder = CONFIG.defaultFolder;
  imageList = [];

  if (!window.allFolderData || window.allFolderData.length === 0) {
    showError('Brak danych folderów. Nie można wczytać obrazów.');
    return;
  }

  if (folder === 'ALL') {
    window.allFolderData.forEach(f => {
      f.images.forEach(img => imageList.push(`${CONFIG.basePath}${f.name}/${img}`));
    });
  } else {
    const selectedFolder = window.allFolderData.find(f => f.name === folder);
    if (!selectedFolder) {
      showError(`Folder "${folder}" nie istnieje.`);
      return;
    }

    selectedFolder.images.forEach(img => imageList.push(`${CONFIG.basePath}${folder}/${img}`));
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

  const spacing = parseFloat(document.getElementById('spacingSlider')?.value) || CONFIG.defaultSpacing;


  textures.forEach((tex, i) => {
    const geo = new THREE.PlaneGeometry(1, 1);

    // 👇 Tutaj wybór materiału na podstawie CONFIG.materialType
    let mat;
    if (CONFIG.materialType === 'standard') {
      mat = new THREE.MeshStandardMaterial({
        map: tex,
        roughness: 0.7,
        metalness: 0.1,
		transparent: CONFIG.transparent ?? false
      });
    } else if (CONFIG.materialType === 'basic') {
      mat = new THREE.MeshBasicMaterial({
        map: tex,
		transparent: CONFIG.transparent ?? false
      });
    }

    const plane = new THREE.Mesh(geo, mat);
    plane.position.x = i * (1 + spacing);

    const skewAngle = (i % 2 === 0 ? CONFIG.skewAngle : -CONFIG.skewAngle);
    plane.rotation.y = THREE.MathUtils.degToRad(skewAngle);

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

function wrapGalleryLoop() {
  const spacing = getSpacing();
  const boxWidth = 1 * uniformBoxScale;
  const totalWidth = planes.length * (boxWidth + spacing);

  if (galleryGroup.position.x > totalWidth / 2) {
    galleryGroup.position.x = -totalWidth / 2;
  }
}




const uniformScaleInput = document.getElementById('uniformScale');
if (uniformScaleInput) {
  uniformScaleInput.addEventListener('input', (e) => {
    const value = parseFloat(e.target.value);
    if (!isNaN(value)) {
      uniformBoxScale = value;
      document.getElementById('scaleDisplay').textContent = uniformBoxScale.toFixed(1);
      updateBoxScale();
    }
  });
}


const bgColorPicker = document.getElementById('bgColorPicker');
if (bgColorPicker) {
  bgColorPicker.addEventListener('input', (e) => {
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

function showError(message) {
  const errorBox = document.getElementById('errorBox');
  if (errorBox) {
    errorBox.innerText = message;
    errorBox.style.display = 'block';
  }
  console.error(message);
}

// Start aplikacji
initThree();
</script>
<script src="main.js" ></script>
</body>
</html>
