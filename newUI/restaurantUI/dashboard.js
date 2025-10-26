(function(){
  const $ = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

  // Simple state
  const state = {
    activeTab: 'profile',
    selectedFile: null,
    previewUrl: null,
    stats: { items: 0, reviews: 0, visits: 0 },
    categories: [
      { id: 1, name: 'المقبلات', items: 2 },
      { id: 2, name: 'الأطباق الرئيسية', items: 5 },
    ]
  };

  // Tabs
  function initTabs(){
    const buttons = $$('.tab-btn');
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-tab');
        if (!id) return;
        state.activeTab = id;
        // buttons active class
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        // panes
        $$('.tab-pane').forEach(p => p.classList.add('hidden')); // hide all
        const pane = $(`#tab-${id}`);
        if (pane){ pane.classList.remove('hidden'); }
        if (window.lucide) window.lucide.createIcons();
      });
    });
  }

  // Stats: GET /api/restaurant/dashboard
  async function loadStats(){
    try {
      const res = await fetch('/api/restaurant/dashboard', { credentials: 'include' });
      const json = await res.json().catch(()=>({}));
      if (json && (json.ok || json.status==='success')){
        const data = json.data || json;
        state.stats.items = Number(data.items||0);
        state.stats.reviews = Number(data.reviews||0);
        state.stats.visits = Number(data.visits||0);
      }
    } catch (e) { /* silent */ }
    $('#stat-items').textContent = state.stats.items;
    $('#stat-reviews').textContent = state.stats.reviews;
    $('#stat-visits').textContent = state.stats.visits;
  }

  // Categories list (static placeholder; replace with API when backend ready)
  function renderCategories(){
    const wrap = $('#categories');
    if (!wrap) return;
    wrap.innerHTML = '';
    state.categories.forEach(cat => {
      const div = document.createElement('div');
      div.className = 'p-4 rounded-lg border flex items-center justify-between bg-slate-100 border-slate-300 hover:border-orange-500 transition-all';
      div.innerHTML = `
        <div>
          <h3 class="font-semibold">${cat.name}</h3>
          <p class="text-sm text-slate-600">${cat.items} أصناف</p>
        </div>
        <div class="flex gap-2">
          <button class="btn btn-outline-secondary btn-sm flex items-center gap-1"><i data-lucide="edit" class="w-4 h-4"></i> تعديل</button>
          <button class="btn btn-outline-secondary btn-sm flex items-center gap-1 text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i> حذف</button>
        </div>`;
      wrap.appendChild(div);
    });
    if (window.lucide) window.lucide.createIcons();
  }

  // Image upload
  function initUpload(){
    const input = $('#upload-input');
    const btn = $('#upload-btn');
    const preview = $('#preview');
    const previewWrap = $('#preview-wrap');

    input?.addEventListener('change', () => {
      const file = input.files?.[0];
      state.selectedFile = file || null;
      if (file){
        const url = URL.createObjectURL(file);
        state.previewUrl = url;
        preview.src = url;
        previewWrap.hidden = false;
        btn.disabled = false;
      } else {
        previewWrap.hidden = true;
        btn.disabled = true;
      }
    });

    btn?.addEventListener('click', async () => {
      if (!state.selectedFile) return;
      btn.disabled = true;
      btn.textContent = 'جار الرفع...';
      try {
        const form = new FormData();
        form.append('image', state.selectedFile);
        const res = await fetch('/api/restaurant/upload-image', {
          method: 'POST',
          credentials: 'include',
          body: form
        });
        const json = await res.json().catch(()=>({}));
        if (json && (json.ok || json.status==='success')){
          const url = json.thumb || json.path;
          if (url){ preview.src = url; }
        }
      } catch(e){ /* silent */ }
      finally {
        btn.textContent = 'رفع الصورة';
        btn.disabled = false;
      }
    });
  }

  // Save profile (placeholder -> connect to PUT /api/restaurant/update)
  function initSaveProfile(){
    const btn = $('#save-profile');
    btn?.addEventListener('click', async () => {
      const payload = {
        name: $('#rest-name')?.value?.trim(),
        city: $('#rest-city')?.value?.trim(),
        description: $('#rest-desc')?.value?.trim(),
        phone: $('#rest-phone')?.value?.trim(),
        email: $('#rest-email')?.value?.trim(),
      };
      btn.disabled = true;
      btn.textContent = 'جار الحفظ...';
      try {
        const res = await fetch('/api/restaurant/update', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify(payload)
        });
        const json = await res.json().catch(()=>({}));
        // Optional: toast / alert
        alert(json.message || (json.ok ? 'تم الحفظ' : 'تم تحديث البيانات'));
        if (payload.name) $('#restaurantName').textContent = payload.name;
      } catch(e){ alert('تعذر حفظ البيانات'); }
      finally { btn.disabled = false; btn.textContent = 'حفظ التغييرات'; }
    });
  }

  // Add Category click (placeholder -> POST /api/menu/add-category)
  function initAddCategory(){
    const btn = $('#add-category');
    btn?.addEventListener('click', async () => {
      const name = prompt('اسم الفئة الجديدة:');
      if (!name) return;
      // Backend call (uncomment when ready)
      // const res = await fetch('/api/menu/add-category', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ name }) });
      // const json = await res.json();
      state.categories.push({ id: Date.now(), name, items: 0 });
      renderCategories();
    });
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    loadStats();
    renderCategories();
    initUpload();
    initSaveProfile();
    initAddCategory();
  });
})();