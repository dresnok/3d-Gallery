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


<div id="refresh-control" style="text-align:center; margin-bottom: 20px;">
  <label for="refreshRate">refreshTime  (s): </label>
<input type="range" id="refreshRate" min="2" max="20" step="1" value="2" />
<span id="refreshDisplay">2</span>s

</div>
<div style="text-align:center; margin-bottom: 20px;">
  <label for="bgColorPicker">backgroundColor: </label>
  <input type="color" id="bgColorPicker" value="#111111" />
</div>
<div id="scale-controls" style="text-align:center; margin-bottom: 20px;">
  <label for="uniformScale">uniformBoxScale (skala):</label><br />
  <input type="range" id="uniformScale" min="2" max="4" step="0.1" value="3" />
  <span id="scaleDisplay">2</span>x
</div>


<div id="three-container" style="height: 400px;
	width: 100%;
	display: block;"></div>


<div id="preview" style="text-align:center; margin-top:20px;">
  <img id="preview-img" src="" alt="Podgląd klikniętej ściany"
       style="max-width:100%; max-height:400px; display:none;" />
  <br />
  <a id="preview-link" href="" target="_blank" style="display:none; color:#ffaa00;">🔗 Otwórz w nowym oknie</a>
</div> 
<script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>
<script>

const baseDir = 'foto'; 
const basePath = baseDir + '/';
const defaultFolder = 'zwierzeta';

const defaultRotationSpeed = 0.005;
const defaultRefreshSeconds = 2;
const minRefreshSeconds = 2;
const maxRefreshSeconds = 20;
const highlightOnHover = true; // podświetlenie
let backgroundColor = '#604242';

let scene, camera, renderer, cube;
let rotationSpeed = defaultRotationSpeed;
let textures = [];
let imageIndex = 0;
let imageList = [];
let refreshTime = defaultRefreshSeconds * 1000;
let refreshInterval;
let uniformBoxScale = 3;

let raycaster = new THREE.Raycaster();
let mouse = new THREE.Vector2();


function initThree() {
  const container = document.getElementById('three-container');
  scene = new THREE.Scene();
  scene.background = new THREE.Color(backgroundColor);
  
  camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
  
  renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(container.clientWidth, container.clientHeight);
  container.appendChild(renderer.domElement);

  const light = new THREE.PointLight(0xffffff, 1, 100);
  light.position.set(10, 10, 10);
  scene.add(light);

  camera.position.z = 5;

  createCube();
  animate();

  renderer.domElement.addEventListener('click', (event) => {
	 
    const rect = renderer.domElement.getBoundingClientRect();
    mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObject(cube);

    if (intersects.length > 0) {
      const faceIndex = intersects[0].face.materialIndex;
      const texIndex = (imageIndex + faceIndex) % textures.length;
      const url = imageList[texIndex];

      const img = document.getElementById('preview-img');
      const link = document.getElementById('preview-link');

      if (url && img && link) {
        img.src = url;
        img.style.display = 'block';
        link.href = url;
        link.style.display = 'inline-block';
      }
    }
  });

  let highlightedFace = null;
  let originalColor = null;

  renderer.domElement.addEventListener('mousemove', (event) => {
	  if (!highlightOnHover) return;
    const rect = renderer.domElement.getBoundingClientRect();
    mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObject(cube);

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
  });
}

function updateBoxScale() {
  if (cube) {
    cube.scale.set(uniformBoxScale, uniformBoxScale, uniformBoxScale);
  }
}

function assignTexturesToCube() {
  if (!cube || textures.length === 0) return;

  const materials = [];
  for (let i = 0; i < 6; i++) {
    const tex = textures[(imageIndex + i) % textures.length]; // przesuwamy obrazy
    materials.push(new THREE.MeshBasicMaterial({ map: tex }));
  }

  cube.material = materials;
}




function createCube() {
  const geometry = new THREE.BoxGeometry();

  if (!cube) {
    // Tymczasowy placeholder
    const placeholderMaterial = new THREE.MeshBasicMaterial({ color: 0x888888 });
    const materials = Array(6).fill(placeholderMaterial);
    
    cube = new THREE.Mesh(geometry, materials);
    cube.scale.set(uniformBoxScale, uniformBoxScale, uniformBoxScale);
    scene.add(cube);
  }

}

function animate() {
  requestAnimationFrame(animate);
  cube.rotation.y += rotationSpeed;
  renderer.render(scene, camera);
}

function changeSpeed(delta) {
  rotationSpeed = Math.max(0.001, rotationSpeed + delta);
  const speedDisplay = document.getElementById('speed-display');
  if (speedDisplay) {
    speedDisplay.textContent = rotationSpeed.toFixed(3);
  }
}

function restartTextureCycle() {
  if (refreshInterval) clearInterval(refreshInterval);

  refreshInterval = setInterval(() => {
    if (textures.length < 6) return;
    imageIndex = (imageIndex + 1) % textures.length;
    assignTexturesToCube();
  }, refreshTime);
}

function loadSelectedFolder() {
  const folderSelect = document.getElementById('folderSelect');
  const folder = folderSelect ? folderSelect.value : defaultFolder;

  imageList = [];

  if (!window.allFolderData || window.allFolderData.length === 0) {
    console.error('Brak danych folderów');
    return;
  }

  if (folder === 'ALL') {
    // Ładuj wszystkie zdjęcia ze wszystkich folderów
    window.allFolderData.forEach(f => {
      f.images.forEach(img => {
        imageList.push(`${basePath}${f.name}/${img}`);
      });
    });
  } else {
    // Ładuj zdjęcia tylko z wybranego folderu
    const selectedFolder = window.allFolderData.find(f => f.name === folder);
    if (selectedFolder) {
      selectedFolder.images.forEach(img => {
        imageList.push(`${basePath}${folder}/${img}`);
      });
    } else {
      console.warn('Nie znaleziono folderu:', folder);
    }
  }

  shuffleArray(imageList);

  preloadAllTextures(imageList).then(loaded => {
    textures = loaded;
    imageIndex = 0;
    assignTexturesToCube();
    restartTextureCycle();
  });
}



function preloadAllTextures(urls) {
  const loader = new THREE.TextureLoader();
  const promises = urls.map(url =>
    new Promise(resolve =>
      loader.load(
        url,
        texture => resolve(texture),
        undefined,
        () => resolve(null)
      )
    )
  );
  return Promise.all(promises).then(loaded => loaded.filter(Boolean));
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



const refreshInput = document.getElementById('refreshRate');
if (refreshInput) {
refreshInput.addEventListener('input', (e) => {
  const seconds = parseInt(e.target.value);
  refreshTime = Math.max(minRefreshSeconds, Math.min(maxRefreshSeconds, seconds)) * 1000;
  const refreshDisplay = document.getElementById('refreshDisplay');
  if (refreshDisplay) refreshDisplay.textContent = seconds;

  // Dodaj log, by sprawdzić
  console.log('Restart cyklu z odświeżaniem co', refreshTime, 'ms');

  restartTextureCycle(); // To powinno działać
});
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


// Start
initThree();
</script>
<script src="main.js" ></script>

</body>
</html>
