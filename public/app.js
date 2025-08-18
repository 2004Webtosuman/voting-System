const API = '/api';
let authToken = localStorage.getItem('token') || '';
let currentUser = null;
let zones = [];
let entrances = [];
let slots = [];
let selectedSlotId = null;

function setAuthUI() {
  const isAuthed = !!authToken && !!currentUser;
  document.getElementById('auth-forms').classList.toggle('hidden', isAuthed);
  document.getElementById('user-info').classList.toggle('hidden', !isAuthed);
  if (isAuthed) {
    document.getElementById('userName').textContent = currentUser.name + ' (' + currentUser.role + ')';
    document.getElementById('adminPanel').classList.toggle('hidden', currentUser.role !== 'admin');
  }
}

async function api(path, options = {}) {
  const headers = Object.assign({ 'Content-Type': 'application/json' }, options.headers || {});
  if (authToken) headers['Authorization'] = 'Bearer ' + authToken;
  const res = await fetch(API + path, Object.assign({}, options, { headers }));
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'API error');
  return data;
}

async function login() {
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const res = await api('/login', { method: 'POST', body: JSON.stringify({ email, password }) });
  authToken = res.token; currentUser = res.user; localStorage.setItem('token', authToken);
  setAuthUI();
  await loadZones();
}

async function register() {
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const name = email.split('@')[0] || 'User';
  await api('/register', { method: 'POST', body: JSON.stringify({ name, email, password }) });
  await login();
}

function logout() {
  authToken = ''; currentUser = null; localStorage.removeItem('token');
  setAuthUI();
}

async function loadZones() {
  zones = await api('/zones');
  const sel = document.getElementById('zoneSelect');
  sel.innerHTML = zones.map(z => `<option value="${z.id}">${z.name}</option>`).join('');
  await loadEntrances();
  await loadSlots();
}

async function loadEntrances() {
  const z = parseInt(document.getElementById('zoneSelect').value, 10);
  entrances = await api(`/zones/${z}/entrances`);
  const sel = document.getElementById('entranceSelect');
  sel.innerHTML = entrances.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
}

async function loadSlots() {
  const z = parseInt(document.getElementById('zoneSelect').value, 10);
  slots = await api(`/zones/${z}/slots`);
  renderMap();
}

function renderMap() {
  const map = document.getElementById('map');
  map.innerHTML = '';
  const z = parseInt(document.getElementById('zoneSelect').value, 10);

  const entranceEls = entrances.map(e => {
    const div = document.createElement('div');
    div.className = 'entrance';
    div.textContent = 'Entrance ' + e.name;
    return div;
  });
  entranceEls.forEach(el => map.appendChild(el));

  slots.forEach(s => {
    const div = document.createElement('div');
    div.className = 'slot' + (s.is_available ? '' : ' unavailable') + (selectedSlotId === s.id ? ' selected' : '');
    div.textContent = s.label;
    div.onclick = () => { if (s.is_available) { selectedSlotId = s.id; renderMap(); } };
    map.appendChild(div);
  });
}

async function findNearest() {
  const z = parseInt(document.getElementById('zoneSelect').value, 10);
  const e = parseInt(document.getElementById('entranceSelect').value, 10);
  const res = await api(`/zones/${z}/nearest-slot?entrance_id=${e}`);
  if (res && res.id) {
    selectedSlotId = res.id; renderMap();
    alert(`Nearest slot: ${res.label} (distance ${res.distance.toFixed(2)})`);
  } else {
    alert('No available slots');
  }
}

async function predict24h() {
  const z = parseInt(document.getElementById('zoneSelect').value, 10);
  const res = await api(`/zones/${z}/predict?hours=24`);
  const msg = res.map(r => `${r.hour}: ${r.predicted_occupied}`).join('\n');
  alert('Predicted occupancy (next 24h):\n' + msg);
}

async function bookSelected() {
  if (!selectedSlotId) { alert('Select a slot'); return; }
  const start = document.getElementById('start').value;
  const end = document.getElementById('end').value;
  if (!start || !end) { alert('Provide start and end'); return; }
  const res = await api('/bookings', { method: 'POST', body: JSON.stringify({ slot_id: selectedSlotId, start_time: start.replace('T', ' ') + ':00', end_time: end.replace('T', ' ') + ':00' }) });
  document.getElementById('bookingResult').innerHTML = `Booking #${res.booking_id}<br/>QR Base64: ${res.qr_code}<br/><img src="https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=${encodeURIComponent(res.qr_code)}" alt="QR"/>`;
  await loadSlots();
}

async function markExit(bookingId, qrBase64) {
  const res = await api(`/bookings/${bookingId}/release`, { method: 'POST', body: JSON.stringify({ exit_qr: qrBase64 }) });
  alert('Exit marked');
  await loadSlots();
}

async function initScanner() {
  const video = document.getElementById('qrVideo');
  const scanBtn = document.getElementById('scanBtn');
  const resultEl = document.getElementById('scanResult');
  if (!('BarcodeDetector' in window)) {
    resultEl.textContent = 'BarcodeDetector not supported. Enter booking ID manually.';
    return;
  }
  const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
  video.srcObject = stream;
  const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
  let running = false;
  scanBtn.onclick = async () => {
    running = !running;
    scanBtn.textContent = running ? 'Stop' : 'Scan QR';
    while (running) {
      try {
        const bitmaps = await createImageBitmap(videoCapture(video));
        const codes = await detector.detect(bitmaps);
        if (codes && codes.length > 0) {
          const raw = codes[0].rawValue;
          resultEl.textContent = 'Scanned: ' + raw;
          const bookingId = ('' + atob(raw)).split(':')[1];
          if (bookingId) {
            await markExit(bookingId, raw);
            running = false; scanBtn.textContent = 'Scan QR';
          }
        }
      } catch (e) {}
      await new Promise(r => setTimeout(r, 500));
    }
  };
}

function videoCapture(video) {
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  return canvas;
}

document.getElementById('loginBtn').onclick = () => login().catch(e => alert(e.message));
document.getElementById('registerBtn').onclick = () => register().catch(e => alert(e.message));
document.getElementById('logoutBtn').onclick = logout;
document.getElementById('zoneSelect').onchange = async () => { await loadEntrances(); await loadSlots(); };
document.getElementById('nearestBtn').onclick = () => findNearest().catch(e => alert(e.message));
document.getElementById('predictBtn').onclick = () => predict24h().catch(e => alert(e.message));
document.getElementById('bookBtn').onclick = () => bookSelected().catch(e => alert(e.message));
document.getElementById('exitBtn').onclick = () => {
  const bookingId = document.getElementById('exitBookingId').value.trim();
  if (!bookingId) { alert('Enter booking ID'); return; }
  const qr = btoa('BOOKING:' + bookingId);
  markExit(bookingId, qr).catch(e => alert(e.message));
};

// Try to fetch current user using token (no endpoint for whoami; assume token valid if zones load)
(async function init() {
  setAuthUI();
  if (authToken) {
    try {
      const who = await api('/whoami');
      currentUser = who.user;
      zones = await api('/zones');
      setAuthUI();
      const sel = document.getElementById('zoneSelect');
      sel.innerHTML = zones.map(z => `<option value="${z.id}">${z.name}</option>`).join('');
      await loadEntrances();
      await loadSlots();
    } catch (e) { logout(); }
  }
  initScanner().catch(() => {});
})();

// Admin handlers
document.getElementById('addZoneBtn').onclick = async () => {
  try {
    const name = document.getElementById('newZoneName').value.trim();
    const description = document.getElementById('newZoneDesc').value.trim();
    await api('/admin/zones', { method: 'POST', body: JSON.stringify({ name, description }) });
    await loadZones();
  } catch (e) { alert(e.message); }
};

document.getElementById('addSlotBtn').onclick = async () => {
  try {
    const zone_id = parseInt(document.getElementById('slotZoneId').value, 10);
    const label = document.getElementById('slotLabel').value.trim();
    const x = parseInt(document.getElementById('slotX').value, 10) || null;
    const y = parseInt(document.getElementById('slotY').value, 10) || null;
    await api('/admin/slots', { method: 'POST', body: JSON.stringify({ zone_id, label, x, y }) });
    if (parseInt(document.getElementById('zoneSelect').value, 10) === zone_id) {
      await loadSlots();
    }
  } catch (e) { alert(e.message); }
};

