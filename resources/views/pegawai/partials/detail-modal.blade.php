{{-- Modal generik dipakai buat 5 jenis data (keluarga/golongan/jabatan/pendidikan/prestasi).
     Field-nya di-generate lewat JS berdasarkan config yang dikirim dari controller,
     supaya nggak perlu 5 modal terpisah. --}}

<div class="modal-overlay" id="detail-modal-overlay">
    <div class="modal-card form-modal">
        <div class="modal-title" id="detail-modal-title"></div>
        <form id="detail-modal-form" method="POST">
            @csrf
            <div id="detail-modal-method-field"></div>
            <div id="detail-modal-fields"></div>
            <div class="form-actions modal-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-outline" onclick="closeDetailModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
const detailFieldConfigs = @json($detailTypes);
const pegawaiId = {{ $pegawai['id'] }};
const detailStoreUrlTemplate = @json(route('pegawai.detail.store', [$pegawai['id'], '__TYPE__']));
const detailUpdateUrlTemplate = @json(route('pegawai.detail.update', [$pegawai['id'], '__TYPE__', '__ITEM__']));

function openDetailModal(type, item = null) {
    const config = detailFieldConfigs[type];
    const isEdit = item !== null;

    document.getElementById('detail-modal-title').textContent = (isEdit ? 'Edit ' : 'Tambah ') + config.title;

    const form = document.getElementById('detail-modal-form');
    form.action = isEdit
        ? detailUpdateUrlTemplate.replace('__TYPE__', type).replace('__ITEM__', item.id)
        : detailStoreUrlTemplate.replace('__TYPE__', type);

    document.getElementById('detail-modal-method-field').innerHTML = isEdit
        ? '<input type="hidden" name="_method" value="PUT">'
        : '';

    const fieldsContainer = document.getElementById('detail-modal-fields');
    fieldsContainer.innerHTML = '';

    config.fields.forEach(field => {
        const wrap = document.createElement('div');
        wrap.className = 'form-group';

        const label = document.createElement('label');
        label.textContent = field.label;
        wrap.appendChild(label);

        let input;
        if (field.type === 'select') {
            input = document.createElement('select');
            input.innerHTML = '<option value="">-- Pilih --</option>' +
                field.options.map(opt => `<option value="${opt}">${opt}</option>`).join('');
        } else if (field.type === 'textarea') {
            input = document.createElement('textarea');
            input.rows = 3;
        } else {
            input = document.createElement('input');
            input.type = field.type === 'date' ? 'date' : 'text';
        }
        input.name = field.key;
        input.required = true;

        if (isEdit && item[field.key] !== undefined) {
            input.value = item[field.key];
        }

        wrap.appendChild(input);
        fieldsContainer.appendChild(wrap);
    });

    document.getElementById('detail-modal-overlay').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detail-modal-overlay').classList.remove('active');
}
</script>
