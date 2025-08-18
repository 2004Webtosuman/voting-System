const api = {
	async me() {
		const r = await fetch('/api/auth.php?action=me');
		return r.json();
	},
	async register(name, email, password) {
		const r = await fetch('/api/auth.php?action=register', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name, email, password }) });
		return r.json();
	},
	async login(email, password) {
		const r = await fetch('/api/auth.php?action=login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password }) });
		return r.json();
	},
	async logout() {
		const r = await fetch('/api/auth.php?action=logout', { method: 'POST' });
		return r.json();
	},
	async zones() {
		const r = await fetch('/api/zones.php');
		return r.json();
	},
	async myBookings() {
		const r = await fetch('/api/bookings.php?action=list');
		return r.json();
	},
	async createBooking(slotId, startsAt, endsAt) {
		const r = await fetch('/api/bookings.php?action=create', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ slot_id: slotId, starts_at: startsAt, ends_at: endsAt }) });
		return r.json();
	},
	async suggestNearest(startSlotId, startsAt, endsAt) {
		const url = `/api/dijkstra.php?start_slot_id=${startSlotId}&starts_at=${encodeURIComponent(startsAt)}&ends_at=${encodeURIComponent(endsAt)}`;
		const r = await fetch(url);
		return r.json();
	},
	async predictions() {
		const r = await fetch('/api/predict.php');
		return r.json();
	}
};

const state = { user: null, zones: [], slots: [], selectedSlotId: null, startSlotId: null };

function $(id){ return document.getElementById(id); }

function formatDTLocal(dt) {
	const d = new Date(dt);
	return d.toISOString().slice(0,16);
}

async function initAuthUI() {
	const me = await api.me();
	state.user = me.user;
	if (state.user) {
		$('guest-actions').classList.add('hidden');
		$('user-actions').classList.remove('hidden');
		$('user-info').textContent = `${state.user.name} (${state.user.email})`;
	} else {
		$('guest-actions').classList.remove('hidden');
		$('user-actions').classList.add('hidden');
	}
	$('register-btn').onclick = async () => {
		const name = $('reg-name').value.trim();
		const email = $('reg-email').value.trim();
		const pass = $('reg-pass').value;
		const res = await api.register(name, email, pass);
		if (res.user) { location.reload(); } else { setStatus(res.error || 'Register failed'); }
	};
	$('login-btn').onclick = async () => {
		const email = $('login-email').value.trim();
		const pass = $('login-pass').value;
		const res = await api.login(email, pass);
		if (res.user) { location.reload(); } else { setStatus(res.error || 'Login failed'); }
	};
	$('logout-btn').onclick = async () => {
		await api.logout();
		location.reload();
	};
}

function setStatus(msg) { $('status').textContent = msg || ''; }

async function loadZones() {
	const z = await api.zones();
	state.zones = z.zones || [];
	state.slots = z.slots || [];
	drawMap();
}

function drawMap() {
	const canvas = $('map');
	const ctx = canvas.getContext('2d');
	ctx.clearRect(0,0,canvas.width, canvas.height);
	// draw zones
	for (const z of state.zones) {
		ctx.fillStyle = '#94a3b8';
		ctx.fillRect(z.x - 10, z.y - 10, 220, 120);
		ctx.fillStyle = '#0f172a';
		ctx.fillText(z.name, z.x, z.y - 14);
	}
	// draw slots
	for (const s of state.slots) {
		const isSelected = state.selectedSlotId === s.id;
		ctx.fillStyle = s.status === 'disabled' ? '#64748b' : '#10b981';
		if (isSelected) ctx.fillStyle = '#f59e0b';
		ctx.beginPath();
		ctx.arc(s.x, s.y, 10, 0, Math.PI*2);
		ctx.fill();
		ctx.fillStyle = '#111827';
		ctx.fillText(String(s.id), s.x - 8, s.y + 20);
	}
}

function pickSlotAt(x, y) {
	for (const s of state.slots) {
		const dx = s.x - x, dy = s.y - y;
		if (Math.sqrt(dx*dx + dy*dy) <= 10) return s;
	}
	return null;
}

function initCanvas() {
	const canvas = $('map');
	canvas.addEventListener('click', (e) => {
		const rect = canvas.getBoundingClientRect();
		const x = e.clientX - rect.left; const y = e.clientY - rect.top;
		const s = pickSlotAt(x, y);
		if (s) { state.selectedSlotId = s.id; if (!state.startSlotId) state.startSlotId = s.id; drawMap(); setStatus(`Selected slot ${s.id}`); }
	});
}

async function loadBookings() {
	if (!state.user) { $('my-bookings').innerHTML = '<li>Login to see bookings</li>'; return; }
	const res = await api.myBookings();
	const items = res.bookings || [];
	$('my-bookings').innerHTML = items.map(b => `<li>#${b.id} • ${b.slot_name} • ${b.starts_at} → ${b.ends_at} • ${b.status}</li>`).join('');
}

function getDateTimeValues() {
	const s = $('start-time').value; const e = $('end-time').value;
	return { s, e };
}

async function initActions() {
	$('suggest-nearest').onclick = async () => {
		const { s, e } = getDateTimeValues();
		if (!state.startSlotId) { setStatus('Select a start slot (click on map)'); return; }
		const res = await api.suggestNearest(state.startSlotId, s, e);
		if (res.result && res.result.target) { state.selectedSlotId = res.result.target; drawMap(); setStatus(`Suggested slot ${res.result.target} at distance ${res.result.distance}`); } else { setStatus('No available slot found'); }
	};
	$('book-selected').onclick = async () => {
		if (!state.user) { setStatus('Login required'); return; }
		if (!state.selectedSlotId) { setStatus('Select a slot'); return; }
		const { s, e } = getDateTimeValues();
		const res = await api.createBooking(state.selectedSlotId, s, e);
		if (res.booking) { setStatus(`Booked! QR token issued.`); loadBookings(); } else { setStatus(res.error || 'Booking failed'); }
	};
}

async function drawPredictions() {
	const res = await api.predictions();
	const preds = res.predictions || [];
	const canvas = $('chart');
	const ctx = canvas.getContext('2d');
	ctx.clearRect(0,0,canvas.width, canvas.height);
	ctx.strokeStyle = '#2563eb';
	ctx.beginPath();
	preds.forEach((p, idx) => {
		const x = (idx / 23) * (canvas.width - 20) + 10;
		const y = canvas.height - 10 - (p.predicted_occupancy * (canvas.height - 20));
		if (idx === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
	});
	ctx.stroke();
}

(async function main(){
	const now = new Date();
	$('start-time').value = formatDTLocal(now);
	$('end-time').value = formatDTLocal(new Date(now.getTime() + 60*60*1000));
	initCanvas();
	await initAuthUI();
	await loadZones();
	await loadBookings();
	await initActions();
	await drawPredictions();
})();