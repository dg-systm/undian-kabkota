<template>
    <div id="page-container" class="sidebar-inverse enable-page-overlay page-header-fixed side-trans-enabled"
        style="min-height: 100vh;">
        <aside id="side-overlay" data-simplebar="init" class="simplebar-scrollable-y">
            <div class="simplebar-wrapper" style="margin: 0px;">
                <div class="simplebar-height-auto-observer-wrapper">
                    <div class="simplebar-height-auto-observer"></div>
                </div>
                <div class="simplebar-mask">
                    <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                        <div class="simplebar-content-wrapper" tabindex="0" role="region"
                            aria-label="scrollable content" style="height: 100%;">
                            <div style="padding: 0px;">
                                <div class="content-header">
                                    <div class="link-fx text-body-color-dark fw-semibold fs-sm">
                                        <i class="fa fa-fw fa-gear"></i> Pengaturan Undian
                                    </div>
                                    <button type="button" class="btn btn-sm btn-alt-danger ms-auto" data-toggle="layout"
                                        data-action="side_overlay_close">
                                        <i class="fa fa-fw fa-times"></i>
                                    </button>
                                </div>
                                <div class="content-side">
                                    <div class="block pull-x">
                                        <div class="block-content block-content-full">

                                            <!-- WILAYAH (dari .env) -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-map-marked-alt me-1 text-primary"></i>
                                                    Wilayah Undian
                                                </label>
                                                <div class="border p-2 rounded bg-dark-op-10">
                                                    <div class="fw-semibold fs-sm">
                                                        {{ wilayah.kabkota || 'Belum diatur' }}
                                                    </div>
                                                </div>
                                                <div v-if="wilayah.error" class="text-danger small mt-1">
                                                    <i class="fa fa-exclamation-triangle me-1"></i>{{ wilayah.error }}
                                                </div>
                                            </div>

                                            <!-- PILIH KECAMATAN -->
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="form-label fw-semibold mb-0">
                                                        <i class="fa fa-map-marker-alt me-1 text-primary"></i>
                                                        Pilih Kecamatan
                                                    </label>
                                                    <button type="button"
                                                        class="btn btn-xs btn-alt-secondary"
                                                        :disabled="wilayah.kecamatan.length === 0"
                                                        @click="toggleSelectAllKecamatan">
                                                        {{ isAllKecamatanSelected ? 'Deselect All' : 'Select All' }}
                                                    </button>
                                                </div>
                                                <div v-if="wilayah.kecamatan.length === 0" class="text-muted small">
                                                    Memuat daftar kecamatan...
                                                </div>
                                                <div v-else class="border p-2 rounded bg-dark-op-10"
                                                    style="max-height: 220px; overflow-y: auto;">
                                                    <div v-for="kec in wilayah.kecamatan" :key="kec.id"
                                                        class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox"
                                                            :id="'kec-' + kec.id" :value="kec.id"
                                                            v-model="selectedKecamatan">
                                                        <label class="form-check-label fs-xs" :for="'kec-' + kec.id">
                                                            {{ kec.nama }}
                                                            <i v-if="isDrawn(kec.id)"
                                                                class="fa fa-check-circle text-success ms-1"
                                                                title="Sudah diundi"></i>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div v-if="drawnKecamatan.length"
                                                    class="d-flex justify-content-between align-items-center mt-1">
                                                    <span class="text-muted" style="font-size: 0.7rem;">
                                                        <i class="fa fa-check-circle text-success me-1"></i>
                                                        {{ drawnKecamatan.length }} kecamatan sudah diundi
                                                    </span>
                                                    <button type="button" class="btn btn-xs btn-alt-danger"
                                                        @click="resetDrawn">
                                                        Reset
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- KATEGORI HADIAH -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-tags me-1 text-primary"></i>
                                                    Kategori Hadiah
                                                </label>
                                                <select class="form-select form-select-sm"
                                                    v-model="selectedCategoryId" @change="onCategoryChange">
                                                    <option value="">-- Pilih Kategori --</option>
                                                    <option v-for="category in prizeCategories" :key="category.id"
                                                        :value="category.id">
                                                        {{ category.name }}
                                                    </option>
                                                </select>
                                            </div>

                                            <!-- PILIH HADIAH -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-gift me-1 text-primary"></i>
                                                    Pilih Hadiah
                                                </label>
                                                <div v-if="!selectedCategoryId" class="text-muted small">
                                                    Pilih kategori untuk melihat hadiah.
                                                </div>
                                                <div v-else-if="filteredPrizes.length === 0" class="text-muted small">
                                                    Tidak ada hadiah pada kategori ini.
                                                </div>
                                                <div v-else class="border p-2 rounded"
                                                    style="max-height: 200px; overflow-y: auto;">
                                                    <div v-for="item in filteredPrizes" :key="item.id"
                                                        class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox"
                                                            :id="`prize-${item.id}`" :value="item.id"
                                                            v-model="selectedPrizeIds">
                                                        <label class="form-check-label fs-xs"
                                                            :for="`prize-${item.id}`">
                                                            {{ item.name }}
                                                            <span class="text-muted">(stok:
                                                                {{ item.available_quantity }})</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- JUMLAH PEMENANG PER KECAMATAN -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-users me-1 text-primary"></i>
                                                    Jumlah Pemenang per Kecamatan
                                                </label>
                                                <input type="number" min="1" class="form-control form-control-sm"
                                                    v-model.number="winnersPerPage">
                                                <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                                    {{ selectedKecamatan.length }} kecamatan ×
                                                    {{ winnersPerPage || 0 }} =
                                                    <strong>{{ requiredWinners }}</strong> pemenang / halaman
                                                </div>
                                            </div>

                                            <!-- SUMMARY -->
                                            <div class="mb-3 p-2 rounded" style="background: rgba(0,0,0,0.05);">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Kecamatan dipilih:</span>
                                                    <strong>{{ selectedKecamatan.length }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Pemenang dibutuhkan:</span>
                                                    <strong
                                                        :class="requiredWinners > 0 ? 'text-primary' : 'text-muted'">
                                                        {{ requiredWinners }} orang
                                                    </strong>
                                                </div>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Total stok hadiah:</span>
                                                    <strong
                                                        :class="totalSelectedStock >= requiredWinners && requiredWinners > 0 ? 'text-success' : 'text-danger'">
                                                        {{ totalSelectedStock }} unit
                                                    </strong>
                                                </div>
                                                <div v-if="settingError" class="mt-1 text-danger small fw-semibold">
                                                    <i class="fa fa-exclamation-triangle me-1"></i>{{ settingError }}
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-expand-arrows-alt me-1 text-primary"></i>
                                                    Ukuran Tombol Start
                                                </label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="range" class="form-range flex-grow-1" min="80"
                                                        max="220" step="5" v-model.number="drawButtonSize">
                                                    <input type="number" class="form-control form-control-sm"
                                                        style="width: 78px;" min="80" max="220" step="5"
                                                        v-model.number="drawButtonSize">
                                                </div>
                                                <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                                    {{ drawButtonSize }}%
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <label class="form-label fw-semibold mb-0">
                                                        <i class="fa fa-arrows-up-down me-1 text-primary"></i>
                                                        Jarak Undian dari Atas
                                                    </label>
                                                    <button type="button" class="btn btn-xs btn-alt-secondary"
                                                        @click="resetUndianTopOffset">
                                                        Reset
                                                    </button>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <input type="range" class="form-range flex-grow-1" min="0"
                                                        max="500" step="5" v-model.number="undianTopOffset">
                                                    <input type="number" class="form-control form-control-sm"
                                                        style="width: 84px;" min="0" max="500" step="5"
                                                        v-model.number="undianTopOffset">
                                                </div>
                                                <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                                    {{ undianTopOffset }} px
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <label class="form-label fw-semibold mb-0">
                                                        <i class="fa fa-table me-1 text-primary"></i>
                                                        Tabel Undian
                                                    </label>
                                                    <button type="button" class="btn btn-xs btn-alt-secondary"
                                                        @click="resetTableAppearance">
                                                        Reset
                                                    </button>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <input type="range" class="form-range flex-grow-1" min="70"
                                                        max="180" step="5" v-model.number="tableSize">
                                                    <input type="number" class="form-control form-control-sm"
                                                        style="width: 84px;" min="70" max="180" step="5"
                                                        v-model.number="tableSize">
                                                </div>
                                                <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                                    Ukuran tabel undian: {{ tableSize }}%
                                                </div>
                                                <div class="row g-2 mt-1">
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Latar Kartu</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tableCardBg">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Garis Tabel</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tableBorderColor">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Judul Kecamatan</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tableTitleColor">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Latar Header</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tableHeaderBg">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Teks Header</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tableHeaderColor">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Latar Isi Tabel</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tableBodyBg">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Teks Isi Tabel</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tableTextColor">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fs-xs mb-0">Teks Hadiah</label>
                                                        <input type="color" class="form-control form-control-sm p-1"
                                                            style="height: 34px;" v-model="tablePrizeColor">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <button class="btn btn-alt-primary w-100" v-on:click="saveSetting"
                                                        :disabled="!!settingError || requiredWinners === 0">
                                                        <i class="fa fa-sync opacity-50 me-1"></i> Simpan
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <a href="/admin" class="btn btn-alt-success w-100"><i
                                                            class="fa fa-sign-in opacity-50 me-1"></i> Sign-in</a>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
        <header id="page-header" style="background-color: rgba(0, 0, 0, 0) !important;">
            <div class="content-header">
                <div class="space-x-1">
                </div>
                <div class="space-x-1">
                    <button type="button" class="btn btn-sm btn-alt-success me-1 page-nav-btn" title="Grand Prize"
                        :disabled="pageSwitching" @click="goToGrandPrize">
                        <span v-if="pageSwitching" class="page-nav-spinner" aria-hidden="true"></span>
                        <i v-else class="fa fa-fw fa-gift"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout"
                        data-action="side_overlay_toggle">
                        <i class="fa fa-fw fa-stream"></i>
                    </button>
                </div>
            </div>
        </header>
        <main id="main-container" style="padding-top:unset !important" @keydown.space.prevent="handleSpaceBar">
            <div class="bg-undian" @click="trigger" tabindex="0" :style="{ backgroundImage: `url(${currentBgImage})` }">
                <div class="bg-black-50">

                    <!-- POSTER UTAMA (pertama kali): hanya menampilkan background poster -->
                    <div v-if="!hasSetup" class="poster-blank"></div>

                    <!-- TAMPILAN UNDIAN -->
                    <div v-else class="content container-fluid undian-content">
                        <div class="undian-center"
                            :style="[{ marginTop: undianTopOffset + 'px' }, tableAppearanceStyle]">
                            <!-- Drawing View -->
                            <div v-if="drawing">
                                <div class="kec-grid"
                                    :class="{ 'kec-grid-single': form.samsats.length <= 1 }"
                                    :style="{ '--kec-cols': kecColumns }">
                                    <div v-for="(kecId, idx) in form.samsats" :key="kecId" class="kec-item">
                                        <div class="kec-card p-2 h-100">
                                            <div
                                                class="kec-card-head d-flex justify-content-between align-items-center pb-1 mb-1">
                                                <span class="kec-title fs-xs text-truncate" style="max-width: 78%;">
                                                    <i class="fa fa-map-marker-alt me-1"></i>{{ kecamatanName(kecId) }}
                                                </span>
                                                <span class="badge bg-danger fs-xs">Drawing...</span>
                                            </div>
                                            <table class="table table-sm table-vcenter kec-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th v-if="form.winnersPerPage > 1" style="width: 8%;">#</th>
                                                        <th style="width: 25%;">No. Polisi</th>
                                                        <th style="width: 42%;">Nama</th>
                                                        <th style="width: 25%;">Hadiah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="row in perKecamatanRows" :key="row">
                                                        <template
                                                            v-if="undianList[idx * form.winnersPerPage + row - 1]">
                                                            <td v-if="form.winnersPerPage > 1">{{ row }}</td>
                                                            <td class="fw-bold text-nowrap">
                                                                {{ undianList[idx * form.winnersPerPage + row - 1].kodeWilayah }}
                                                                {{ undianList[idx * form.winnersPerPage + row - 1].angka }}
                                                                {{ undianList[idx * form.winnersPerPage + row - 1].kodeSeri }}
                                                            </td>
                                                            <td class="text-truncate" style="max-width: 120px;">
                                                                <div class="fw-semibold text-truncate">Acak data...</div>
                                                            </td>
                                                            <td class="text-truncate kec-prize fw-semibold"
                                                                style="max-width: 70px;">
                                                                {{ form.prizeNames[(idx * form.winnersPerPage + row - 1) %
                                                                    form.prizeNames.length] || '-' }}
                                                            </td>
                                                        </template>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Static View -->
                            <div v-else>
                                <div class="kec-grid"
                                    :class="{ 'kec-grid-single': form.samsats.length <= 1 }"
                                    :style="{ '--kec-cols': kecColumns }">
                                    <div v-for="(kecId, idx) in form.samsats" :key="kecId" class="kec-item">
                                        <div class="kec-card p-2 h-100">
                                            <div
                                                class="kec-card-head d-flex justify-content-between align-items-center pb-1 mb-1">
                                                <span class="kec-title fs-xs text-truncate" style="max-width: 78%;">
                                                    <i class="fa fa-map-marker-alt me-1"></i>{{ kecamatanName(kecId) }}
                                                </span>
                                                <span class="badge bg-primary fs-xs">{{ form.winnersPerPage }}
                                                    Pemenang</span>
                                            </div>
                                            <table class="table table-sm table-vcenter kec-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th v-if="form.winnersPerPage > 1" style="width: 8%;">#</th>
                                                        <th style="width: 25%;">No. Polisi</th>
                                                        <th style="width: 42%;">Nama</th>
                                                        <th style="width: 25%;">Hadiah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(item, row) in winnersByKecamatan(kecId)"
                                                        :key="item.id || row">
                                                        <td v-if="form.winnersPerPage > 1">{{ row + 1 }}</td>
                                                        <td class="fw-bold text-nowrap">{{ item.kodeWilayah }} {{
                                                            item.angka }} {{ item.kodeSeri }}</td>
                                                        <td class="text-truncate" style="max-width: 120px;">
                                                            <div class="fw-semibold text-truncate">{{ item.nama ||
                                                                'Belum teracak' }}</div>
                                                        </td>
                                                        <td class="text-truncate kec-prize fw-semibold"
                                                            style="max-width: 70px;">
                                                            {{ item.prize?.name || 'Belum teracak' }}</td>
                                                    </tr>
                                                    <tr v-if="winnersByKecamatan(kecId).length === 0">
                                                        <td :colspan="form.winnersPerPage > 1 ? 4 : 3"
                                                            class="text-center text-muted py-2">Belum diundi.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.samsats.length === 0" class="text-center text-muted py-3">
                                    Belum ada kecamatan yang dipilih.
                                </div>
                            </div>

                            <!-- Draw Button & Hint -->
                            <div class="text-center draw-button-wrap" v-if="form.samsats.length > 0">
                                <button type="button" class="btn btn-rounded btn-xl fw-semibold animated shake"
                                    id="btnDraw"
                                    :class="(shuffling ? 'btn-warning' : (drawing ? 'btn-danger' : 'btn-success'))"
                                    :style="drawButtonStyle" @click="drawHandle"
                                    v-bind:disabled="disableTrigger || shuffling" autofocus>
                                    {{ shuffling ? 'Sedang Mengacak' : (drawing ? 'Stop Pengundian!' : 'Start Pengundian!')
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.bg-undian {
    background-repeat: no-repeat;
    background-position: center center;
    background-size: cover;
    min-height: 100vh;
    height: 100vh;
}

#page-container {
    overflow: auto;
    min-height: 100vh;
    scrollbar-width: none;
    /* Firefox */
}

#page-container::-webkit-scrollbar {
    display: none;
    /* Chrome, Safari, Edge */
}

.h1-custom {
    font-size: 5.571429rem;
}

.bg-black-op-50 {
    background-color: rgba(0, 0, 0, .75) !important;
}

.bg-black-50 {
    min-height: 100vh;
    background-color: transparent !important;
}

.undian-content {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 140px);
}

/* Form undian: jarak dari atas diatur lewat pengaturan (undianTopOffset) */
.undian-center {
    margin-bottom: auto;
    width: 100%;
}

/* Daftar kartu: baris terakhir otomatis di tengah.
   Jumlah kolom diatur lewat --kec-cols (lihat kecColumns):
   - 4 kartu  -> 2 + 2
   - 5 kartu  -> 3 + 2 (2 kartu bawah di tengah)
   - 6 kartu  -> 3 + 3
*/
.kec-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch;
    gap: 1rem;
    width: 100%;
}

.kec-item {
    flex: 0 0 100%;
}

@media (min-width: 768px) {
    .kec-item {
        flex: 0 0 calc((100% - (var(--kec-cols, 2) - 1) * 1rem) / var(--kec-cols, 2));
    }

    /* Bila hanya 1 kecamatan, tampilkan satu kartu di tengah */
    .kec-grid.kec-grid-single .kec-item {
        flex-basis: min(100%, 640px);
    }
}

/* Kartu pemenang dibuat cerah agar mudah terlihat */
.kec-card {
    background: var(--kec-card-bg, #ffffff);
    border: 1px solid var(--kec-border, rgba(13, 110, 253, 0.18));
    border-radius: 0.6rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.30);
    color: var(--kec-td-color, #1f2937);
    width: 100%;
}

/* Jarak tombol start dari kartu pengundian */
.draw-button-wrap {
    margin-top: 2.5rem;
    margin-bottom: 1.5rem;
}

.kec-card-head {
    border-bottom: 1px solid var(--kec-border, #e5e7eb);
}

.kec-title {
    color: var(--kec-title-color, #0d6efd);
    font-weight: 700;
    text-transform: uppercase;
}

.kec-prize {
    color: var(--kec-prize-color, #0d6efd);
}

.kec-table {
    font-size: var(--kec-table-font, 0.72rem);
    line-height: 1.1;
    margin-bottom: 0;
    color: var(--kec-td-color, #1f2937);
}

.kec-table>thead>tr>th {
    background: var(--kec-th-bg, #eef2ff);
    color: var(--kec-th-color, #3730a3);
    border-color: var(--kec-border, #dbe4ff);
    text-align: center;
    vertical-align: middle;
    padding: var(--kec-table-pad, 0.25rem);
}

.kec-table>tbody>tr>td {
    background: var(--kec-td-bg, #ffffff);
    text-align: center;
    vertical-align: middle;
    border-color: var(--kec-border, #eef0f4);
    color: var(--kec-td-color, #1f2937);
    padding: var(--kec-table-pad, 0.25rem);
}

.undian-grid {
    flex: 1 1 auto;
    overflow: auto;
}

.table {
    margin-left: auto;
    margin-right: auto;
    text-align: center;
}

.table th,
.table td {
    text-align: center;
    vertical-align: middle;
}

#btnDraw {
    margin-top: 0;
}

/* Poster utama: hanya background (poster) */
.poster-blank {
    min-height: 100vh;
    width: 100%;
}

.page-nav-btn {
    min-width: 34px;
    min-height: 31px;
}

.page-nav-spinner {
    display: inline-block;
    width: 0.95rem;
    height: 0.95rem;
    border: 2px solid rgba(255, 255, 255, 0.45);
    border-top-color: #fff;
    border-radius: 50%;
    vertical-align: -0.15em;
    animation: page-nav-spin 0.65s linear infinite;
}

@keyframes page-nav-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<script>
import { ref, reactive, onMounted, computed, watch, inject } from "vue";
import axios from "axios";
import confetti from 'canvas-confetti';
import bgImage from '@/../images/background1-min.webp'
import bgImageInitial from '@/../images/background1-min3.webp'

export default {
    setup() {
        const appName = inject('appName');
        const drawing = ref(false);
        const shuffling = ref(false); // 3s lock when starting
        const requestPending = ref(false);
        const winnersDetermined = ref(false);
        const pageSwitching = ref(false);
        const drawButtonSize = ref(Number(localStorage.getItem('regularDrawButtonSize')) || 100);
        const platJateng = ['H', 'K', 'AD', 'G', 'R', 'AA'];
        const disableTrigger = ref(true);

        const updateDisableTrigger = () => {
            disableTrigger.value = shuffling.value || requestPending.value || winnersDetermined.value;
        };

        // --- Wilayah & Pengaturan state ---
        const wilayah = reactive({
            kabkota: inject('kabkota') || '',
            kecamatan: [],
            kecamatanSamsat: {},
            samsatIds: [],
            winnersPerPage: Number(inject('winnersPerPage')) || 5,
            error: '',
        });
        const selectedKecamatan = ref([]);
        const selectedCategoryId = ref('');
        const selectedPrizeIds = ref([]);
        const prizeOptions = ref([]);
        const winnersPerPage = ref(wilayah.winnersPerPage || 5);

        // Nama kecamatan berdasarkan id (id = kunci unik).
        const kecamatanName = (id) => {
            const found = wilayah.kecamatan.find(k => k.id === id);
            return found ? found.nama : id;
        };

        // Kecamatan yang sudah diundi (ditandai centang), disimpan di localStorage per wilayah.
        const drawnKecamatan = ref([]);
        const drawnStorageKey = () => `drawnKecamatan:${wilayah.kabkota || 'default'}`;
        const isDrawn = (id) => drawnKecamatan.value.includes(id);

        const loadDrawn = () => {
            try {
                const raw = localStorage.getItem(drawnStorageKey());
                drawnKecamatan.value = raw ? JSON.parse(raw) : [];
            } catch (e) {
                drawnKecamatan.value = [];
            }
        };

        const markDrawn = (ids) => {
            const set = new Set(drawnKecamatan.value);
            ids.forEach(id => set.add(id));
            drawnKecamatan.value = Array.from(set);
            try {
                localStorage.setItem(drawnStorageKey(), JSON.stringify(drawnKecamatan.value));
            } catch (e) { /* abaikan */ }
        };

        const resetDrawn = () => {
            drawnKecamatan.value = [];
            try {
                localStorage.removeItem(drawnStorageKey());
            } catch (e) { /* abaikan */ }
        };

        // Kategori hadiah (mirip Grand Prize), urut dari data API
        const prizeCategories = computed(() => {
            const categories = [];
            prizeOptions.value.forEach(item => {
                const category = item.prize_category || { id: null, name: 'Tanpa Kategori' };
                if (!categories.some(c => c.id === category.id)) {
                    categories.push({ id: category.id, name: category.name });
                }
            });
            return categories;
        });

        const extractGram = (name) => {
            const cleaned = String(name || '').replace(',', '.');
            const match = cleaned.match(/([0-9]+(?:\.[0-9]+)?)/);
            return match ? parseFloat(match[1]) : 0;
        };

        const filteredPrizes = computed(() => {
            if (!selectedCategoryId.value) return [];
            return prizeOptions.value
                .filter(item => item.prize_category?.id === selectedCategoryId.value)
                .sort((a, b) => extractGram(b.name) - extractGram(a.name));
        });

        const onCategoryChange = () => {
            if (!selectedCategoryId.value) {
                selectedPrizeIds.value = [];
                return;
            }
            // Pilih semua hadiah dalam kategori sebagai default (bisa di-uncheck manual).
            selectedPrizeIds.value = filteredPrizes.value.map(item => item.id);
        };

        // Background dinamis: jika sudah ada setup, gunakan bgImage, else poster awal
        const currentBgImage = computed(() => {
            return hasSetup.value ? bgImage : bgImageInitial;
        });

        const drawButtonStyle = computed(() => {
            const scale = Math.min(Math.max(Number(drawButtonSize.value) || 100, 80), 220) / 100;

            return {
                fontSize: `${1.15 * scale}rem`,
                minHeight: `${52 * scale}px`,
                padding: `${12 * scale}px ${28 * scale}px`,
                minWidth: `${230 * scale}px`,
                lineHeight: '1.15',
            };
        });

        watch(drawButtonSize, (value) => {
            const normalized = Math.min(Math.max(Number(value) || 100, 80), 220);
            if (normalized !== value) {
                drawButtonSize.value = normalized;
                return;
            }
            localStorage.setItem('regularDrawButtonSize', String(normalized));
        });

        // Jarak undian dari atas (px), tersimpan permanen di localStorage.
        const DEFAULT_UNDIAN_TOP = 24;
        const storedUndianTop = localStorage.getItem('regularUndianTopOffset');
        const undianTopOffset = ref(storedUndianTop !== null ? Number(storedUndianTop) : DEFAULT_UNDIAN_TOP);

        watch(undianTopOffset, (value) => {
            const normalized = Math.min(Math.max(Number(value) || 0, 0), 500);
            if (normalized !== value) {
                undianTopOffset.value = normalized;
                return;
            }
            localStorage.setItem('regularUndianTopOffset', String(normalized));
        });

        const resetUndianTopOffset = () => {
            undianTopOffset.value = DEFAULT_UNDIAN_TOP;
            localStorage.setItem('regularUndianTopOffset', String(DEFAULT_UNDIAN_TOP));
        };

        // Tampilan tabel undian (ukuran & warna), tersimpan permanen di localStorage.
        const TABLE_DEFAULTS = {
            size: 100,
            cardBg: '#ffffff',
            borderColor: '#e5e7eb',
            titleColor: '#0d6efd',
            headerBg: '#eef2ff',
            headerColor: '#3730a3',
            bodyBg: '#ffffff',
            textColor: '#1f2937',
            prizeColor: '#0d6efd',
        };

        const readStoredValue = (key, fallback) => {
            const value = localStorage.getItem(key);
            return value !== null && value !== '' ? value : fallback;
        };

        const tableSize = ref(Number(readStoredValue('undianTableSize', TABLE_DEFAULTS.size)) || TABLE_DEFAULTS.size);
        const tableCardBg = ref(readStoredValue('undianTableCardBg', TABLE_DEFAULTS.cardBg));
        const tableBorderColor = ref(readStoredValue('undianTableBorderColor', TABLE_DEFAULTS.borderColor));
        const tableTitleColor = ref(readStoredValue('undianTableTitleColor', TABLE_DEFAULTS.titleColor));
        const tableHeaderBg = ref(readStoredValue('undianTableHeaderBg', TABLE_DEFAULTS.headerBg));
        const tableHeaderColor = ref(readStoredValue('undianTableHeaderColor', TABLE_DEFAULTS.headerColor));
        const tableBodyBg = ref(readStoredValue('undianTableBodyBg', TABLE_DEFAULTS.bodyBg));
        const tableTextColor = ref(readStoredValue('undianTableTextColor', TABLE_DEFAULTS.textColor));
        const tablePrizeColor = ref(readStoredValue('undianTablePrizeColor', TABLE_DEFAULTS.prizeColor));

        watch(tableSize, (value) => {
            const normalized = Math.min(Math.max(Number(value) || 100, 70), 180);
            if (normalized !== value) {
                tableSize.value = normalized;
                return;
            }
            localStorage.setItem('undianTableSize', String(normalized));
        });

        [
            [tableCardBg, 'undianTableCardBg'],
            [tableBorderColor, 'undianTableBorderColor'],
            [tableTitleColor, 'undianTableTitleColor'],
            [tableHeaderBg, 'undianTableHeaderBg'],
            [tableHeaderColor, 'undianTableHeaderColor'],
            [tableBodyBg, 'undianTableBodyBg'],
            [tableTextColor, 'undianTableTextColor'],
            [tablePrizeColor, 'undianTablePrizeColor'],
        ].forEach(([refValue, key]) => {
            watch(refValue, (value) => localStorage.setItem(key, value));
        });

        const tableAppearanceStyle = computed(() => {
            const scale = Math.min(Math.max(Number(tableSize.value) || 100, 70), 180) / 100;

            return {
                '--kec-table-font': `${(0.72 * scale).toFixed(3)}rem`,
                '--kec-table-pad': `${(0.25 * scale).toFixed(3)}rem`,
                '--kec-card-bg': tableCardBg.value,
                '--kec-border': tableBorderColor.value,
                '--kec-title-color': tableTitleColor.value,
                '--kec-th-bg': tableHeaderBg.value,
                '--kec-th-color': tableHeaderColor.value,
                '--kec-td-bg': tableBodyBg.value,
                '--kec-td-color': tableTextColor.value,
                '--kec-prize-color': tablePrizeColor.value,
            };
        });

        const resetTableAppearance = () => {
            tableSize.value = TABLE_DEFAULTS.size;
            tableCardBg.value = TABLE_DEFAULTS.cardBg;
            tableBorderColor.value = TABLE_DEFAULTS.borderColor;
            tableTitleColor.value = TABLE_DEFAULTS.titleColor;
            tableHeaderBg.value = TABLE_DEFAULTS.headerBg;
            tableHeaderColor.value = TABLE_DEFAULTS.headerColor;
            tableBodyBg.value = TABLE_DEFAULTS.bodyBg;
            tableTextColor.value = TABLE_DEFAULTS.textColor;
            tablePrizeColor.value = TABLE_DEFAULTS.prizeColor;

            localStorage.setItem('undianTableSize', String(TABLE_DEFAULTS.size));
            localStorage.setItem('undianTableCardBg', TABLE_DEFAULTS.cardBg);
            localStorage.setItem('undianTableBorderColor', TABLE_DEFAULTS.borderColor);
            localStorage.setItem('undianTableTitleColor', TABLE_DEFAULTS.titleColor);
            localStorage.setItem('undianTableHeaderBg', TABLE_DEFAULTS.headerBg);
            localStorage.setItem('undianTableHeaderColor', TABLE_DEFAULTS.headerColor);
            localStorage.setItem('undianTableBodyBg', TABLE_DEFAULTS.bodyBg);
            localStorage.setItem('undianTableTextColor', TABLE_DEFAULTS.textColor);
            localStorage.setItem('undianTablePrizeColor', TABLE_DEFAULTS.prizeColor);
        };

        // Form yang di-commit saat klik Simpan
        const form = reactive({
            samsats: [],
            prize_ids: [],
            prizeNames: [],
            quantity: 0,
            winnersPerPage: 5,
            samsatIdMap: {},
        });

        const hasSetup = computed(() => form.samsats.length > 0 && form.prize_ids.length > 0);

        const isAllKecamatanSelected = computed(() => {
            if (wilayah.kecamatan.length === 0) return false;
            return wilayah.kecamatan.every(kec => selectedKecamatan.value.includes(kec.id));
        });

        const toggleSelectAllKecamatan = () => {
            if (wilayah.kecamatan.length === 0) return;
            if (isAllKecamatanSelected.value) {
                selectedKecamatan.value = [];
            } else {
                selectedKecamatan.value = wilayah.kecamatan.map(kec => kec.id);
            }
        };

        const requiredWinners = computed(() => {
            return selectedKecamatan.value.length * (Number(winnersPerPage.value) || 0);
        });

        const totalSelectedStock = computed(() => {
            return selectedPrizeIds.value.reduce((sum, id) => {
                const prize = prizeOptions.value.find(p => p.id === id);
                return sum + (prize ? prize.available_quantity : 0);
            }, 0);
        });

        const settingError = computed(() => {
            if (wilayah.error) return wilayah.error;
            if (!wilayah.kecamatan.length) return 'Daftar kecamatan belum tersedia. Periksa APP_KABKOTA di .env.';
            if (selectedKecamatan.value.length === 0) return 'Pilih minimal 1 kecamatan.';
            if (!selectedCategoryId.value) return 'Pilih kategori hadiah.';
            if (selectedPrizeIds.value.length === 0) return 'Pilih minimal 1 hadiah.';
            if (!winnersPerPage.value || Number(winnersPerPage.value) < 1) {
                return 'Jumlah pemenang per kecamatan minimal 1.';
            }
            if (totalSelectedStock.value < requiredWinners.value) {
                return `Stok hadiah (${totalSelectedStock.value}) kurang dari pemenang yang dibutuhkan (${requiredWinners.value}).`;
            }
            return '';
        });

        const undianList = ref([]);
        const winnerList = ref([]);

        const perKecamatanRows = computed(() => {
            return Array.from({ length: Number(form.winnersPerPage) || 0 }, (_, i) => i + 1);
        });

        // Jumlah kolom kartu agar tersusun seimbang (± 2 baris):
        // 4 kartu -> 2 kolom (2+2), 5 -> 3 kolom (3+2), 6 -> 3 kolom (3+3), dst.
        const kecColumns = computed(() => {
            const n = form.samsats.length;
            if (n <= 2) return Math.max(n, 1);
            return Math.min(Math.ceil(n / 2), 4);
        });

        const initUndianList = () => {
            undianList.value = [];
            const per = Number(form.winnersPerPage) || 0;
            const total = form.samsats.length * per;
            for (let i = 0; i < total; i++) {
                undianList.value.push({
                    id: i + 1,
                    kodeWilayah: '??',
                    angka: '????',
                    kodeSeri: '??',
                    interval: {
                        wilayah: null,
                        angka: null,
                        seri: null
                    },
                    drawing: false,
                    id_kendaraan: '',
                    nama: 'Belum teracak',
                    alamat: 'Belum teracak',
                    roda: '-',
                    lokasi: form.samsats[Math.floor(i / per)] || '',
                    prize: { id: null, name: 'Belum teracak' }
                });
            }
        };

        const winnersByKecamatan = (kec) => {
            return undianList.value.filter(u => u.lokasi === kec);
        };

        const getRandomLetter = (length) => {
            const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            let letter = '';
            for (let i = 0; i < length; i++) {
                letter += alphabet[Math.floor(Math.random() * alphabet.length)];
            }
            return letter;
        };

        const startDrawingRow = (row) => {
            row.drawing = true;

            row.interval.wilayah = setInterval(() => {
                row.kodeWilayah = platJateng[Math.floor(Math.random() * platJateng.length)];
            }, 75);

            row.interval.angka = setInterval(() => {
                row.angka = Math.floor(Math.random() * 9999 + 1);
            }, 75);

            row.interval.seri = setInterval(() => {
                row.kodeSeri = getRandomLetter(Math.floor(Math.random() * 3 + 1));
            }, 75);
        };

        const stopDrawingRow = (row) => {
            row.drawing = false;

            clearInterval(row.interval.wilayah);
            clearInterval(row.interval.angka);
            clearInterval(row.interval.seri);
        };

        const pickWinner = async () => {
            requestPending.value = true;
            updateDisableTrigger();
            try {
                const response = await axios.post("api/draw/pick", {
                    prize_ids: form.prize_ids,
                    samsats: form.samsats,
                    per_samsat: form.winnersPerPage,
                    samsat_ids: form.samsatIdMap,
                });

                winnerList.value = response.data.data;
                getOptionsPrize();
            } catch (error) {
                undianList.value.forEach(row => stopDrawingRow(row));
                alert(error.response?.data.message || 'Terjadi kesalahan saat pengundian.');
                console.error('Gagal update:', error.response?.data);
            } finally {
                requestPending.value = false;
                updateDisableTrigger();
            }
        }

        const parseWinner = () => {
            undianList.value = winnerList.value.map((item, index) => {
                const parts = item.no_polisi.match(/[a-zA-Z]+|[0-9]+/g) || ['XX', '0000', 'XX'];
                const kodeWilayah = parts[0] || 'XX';
                const angka = parts[1] || '0000';
                const kodeSeri = parts.slice(2).join('') || 'XX';

                return {
                    id: index + 1,
                    kodeWilayah,
                    angka,
                    kodeSeri,
                    interval: {
                        wilayah: null,
                        angka: null,
                        seri: null
                    },
                    drawing: true,
                    id_kendaraan: item.id_kendaraan,
                    nama: item.nama,
                    alamat: item.alamat,
                    roda: item.roda,
                    lokasi: item.lokasi,
                    prize: item.prize || { name: '-' }
                };
            });
        }

        const drawHandle = async () => {
            if (disableTrigger.value) return;

            if (!drawing.value) {
                drawing.value = true;
                shuffling.value = true;
                updateDisableTrigger();
                undianList.value.forEach(row => startDrawingRow(row));

                const animationDelay = new Promise(resolve => setTimeout(resolve, 3000));
                await Promise.all([animationDelay, pickWinner()]);

                shuffling.value = false;
                updateDisableTrigger();
                // keep drawing true until user stops
            } else {
                undianList.value.forEach(row => stopDrawingRow(row));
                parseWinner();
                markDrawn(form.samsats);
                drawing.value = false;
                winnersDetermined.value = true;
                updateDisableTrigger();

                // Trigger confetti effect
                confetti({
                    particleCount: 500,
                    angle: 60,
                    spread: 500,
                    startVelocity: 60,
                    scalar: 1.2,
                    origin: { x: 0, y: 0 }
                });

                confetti({
                    particleCount: 500,
                    angle: 120,
                    spread: 500,
                    startVelocity: 60,
                    scalar: 1.2,
                    origin: { x: 1, y: 0 }
                });
            }
        };

        const saveSetting = () => {
            if (settingError.value) {
                alert(settingError.value);
                return;
            }

            const names = selectedPrizeIds.value.map(id => {
                const p = prizeOptions.value.find(p => p.id === id);
                return p ? p.name : '';
            }).filter(Boolean);

            form.samsats = [...selectedKecamatan.value];
            form.prize_ids = [...selectedPrizeIds.value];
            form.prizeNames = names;
            form.winnersPerPage = Number(winnersPerPage.value) || 1;
            form.quantity = form.samsats.length * form.winnersPerPage;

            // Petakan tiap kecamatan (id) ke Samsat-nya.
            // Kecamatan dalam satu Kabupaten/Kota bisa berada di Samsat berbeda.
            form.samsatIdMap = form.samsats.reduce((map, kecId) => {
                const samsatId = wilayah.kecamatanSamsat?.[kecId];
                map[kecId] = samsatId ? [samsatId] : wilayah.samsatIds;
                return map;
            }, {});

            const el = document.getElementById('page-container');
            if (el) {
                el.classList.remove('side-overlay-o');
            }

            initUndianList();
            disableTrigger.value = false;
        };

        const trigger = () => {
            // document.getElementById('btnDraw').focus();
        };

        const goToGrandPrize = () => {
            if (pageSwitching.value) return;
            pageSwitching.value = true;
            window.location.href = '/grand-prize';
        };

        const handleSpaceBar = (event) => {
            if (event.code === 'Space' || event.key === ' ') {
                event.preventDefault();
                if (disableTrigger.value) return;
                drawHandle(drawing.value);
            }
        };

        const getOptionsPrize = async () => {
            try {
                const response = await axios.get('/api/get-data/prize');
                prizeOptions.value = response.data;
            } catch (error) {
                console.error('Gagal memuat data hadiah:', error);
            }
        };

        const getWilayah = async () => {
            try {
                const response = await axios.get('/api/get-data/wilayah');
                wilayah.kabkota = response.data.kabkota || wilayah.kabkota;
                wilayah.kecamatan = response.data.kecamatan || [];
                wilayah.kecamatanSamsat = response.data.kecamatan_samsat || {};
                wilayah.samsatIds = response.data.samsat_ids || [];
                wilayah.error = response.data.error || '';

                loadDrawn();

                if (response.data.winners_per_page) {
                    wilayah.winnersPerPage = Number(response.data.winners_per_page);
                    if (!form.prize_ids.length) {
                        winnersPerPage.value = wilayah.winnersPerPage;
                    }
                }
            } catch (error) {
                wilayah.error = 'Gagal memuat data wilayah.';
                console.error('Gagal memuat data wilayah:', error);
            }
        };

        onMounted(async () => {
            getOptionsPrize();
            getWilayah();
        });

        return {
            appName,
            prizeOptions,
            prizeCategories,
            filteredPrizes,
            selectedCategoryId,
            selectedPrizeIds,
            onCategoryChange,
            platJateng,
            wilayah,
            selectedKecamatan,
            kecamatanName,
            drawnKecamatan,
            isDrawn,
            resetDrawn,
            winnersPerPage,
            isAllKecamatanSelected,
            toggleSelectAllKecamatan,
            requiredWinners,
            totalSelectedStock,
            settingError,
            saveSetting,
            form,
            hasSetup,
            undianList,
            perKecamatanRows,
            kecColumns,
            winnersByKecamatan,
            startDrawingRow,
            stopDrawingRow,
            drawHandle,
            handleSpaceBar,
            goToGrandPrize,

            disableTrigger,
            trigger,
            drawing,
            shuffling,
            pageSwitching,
            currentBgImage,
            drawButtonSize,
            drawButtonStyle,
            undianTopOffset,
            resetUndianTopOffset,
            tableSize,
            tableCardBg,
            tableBorderColor,
            tableTitleColor,
            tableHeaderBg,
            tableHeaderColor,
            tableBodyBg,
            tableTextColor,
            tablePrizeColor,
            tableAppearanceStyle,
            resetTableAppearance
        };
    }
}
</script>
