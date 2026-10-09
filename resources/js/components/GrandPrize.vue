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
                                        <i class="fa fa-fw fa-gear"></i> Pengaturan Grand Prize
                                    </div>
                                    <button type="button" class="btn btn-sm btn-alt-danger ms-auto" data-toggle="layout"
                                        data-action="side_overlay_close">
                                        <i class="fa fa-fw fa-times"></i>
                                    </button>
                                </div>
                                <div class="content-side">
                                    <div class="block pull-x">
                                        <div class="block-content block-content-full">

                                            <!-- PRIZE CATEGORY -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    Kategori Hadiah
                                                </label>
                                                <select v-model="selectedCategoryId" @change="onCategoryChange"
                                                    class="form-select form-select-sm">
                                                    <option value="">-- Pilih Kategori --</option>
                                                    <option v-for="category in prizeCategories" :key="category.id"
                                                        :value="category.id">
                                                        {{ category.name }}
                                                    </option>
                                                </select>
                                            </div>

                                            <!-- PRIZE MULTI-SELECT -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    Hadiah Grand Prize
                                                </label>
                                                <div v-if="!selectedCategoryId" class="text-muted small">
                                                    Pilih kategori untuk melihat hadiah.
                                                </div>
                                                <div v-else-if="filteredPrizes.length === 0" class="text-muted small">
                                                    Tidak ada hadiah pada kategori ini.
                                                </div>
                                                <div v-else class="border rounded p-2"
                                                    style="max-height: 240px; overflow-y: auto;">
                                                    <div v-for="item in filteredPrizes" :key="item.id"
                                                        class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox"
                                                            :id="`prize-${item.id}`" :value="item.id"
                                                            v-model="selectedPrizeIds">
                                                        <label class="form-check-label fs-xs" :for="`prize-${item.id}`">
                                                            {{ item.name }}
                                                            <span class="text-muted">(stok: {{ item.available_quantity
                                                                }})</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- QUANTITY -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-users me-1 text-primary"></i>
                                                    Jumlah Pemenang
                                                </label>
                                                <input type="number" min="1" :max="maxQuantity" v-model="quantity"
                                                    class="form-control" placeholder="Masukan Jumlah..">
                                                <div class="text-muted mt-1" style="font-size:0.75rem;">
                                                    Maks: <strong>{{ maxQuantity }}</strong> (stok tersedia)
                                                </div>
                                            </div>

                                            <!-- SCOPE INFO -->
                                            <div class="mb-3 p-2 rounded"
                                                style="background: rgba(255,215,0,0.08); border: 1px solid rgba(255,215,0,0.3);">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-globe text-warning"></i>
                                                    <div>
                                                        <div class="fw-semibold fs-sm text-warning">Mode Global</div>
                                                        <div class="text-muted" style="font-size:0.72rem;">
                                                            Mengundi dari <strong>semua plat</strong> yang terdaftar
                                                            (H, K, AD, G, R, AA)
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fa fa-expand-arrows-alt me-1 text-warning"></i>
                                                    Ukuran Tombol Start
                                                </label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="range" class="form-range flex-grow-1" min="80"
                                                        max="220" step="5" v-model.number="drawButtonSize">
                                                    <input type="number" class="form-control form-control-sm"
                                                        style="width: 78px;" min="80" max="220" step="5"
                                                        v-model.number="drawButtonSize">
                                                </div>
                                                <div class="text-muted mt-1" style="font-size:0.72rem;">
                                                    {{ drawButtonSize }}%
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <label class="form-label fw-semibold mb-0">
                                                        <i class="fa fa-arrows-up-down me-1 text-warning"></i>
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
                                                <div class="text-muted mt-1" style="font-size:0.72rem;">
                                                    {{ undianTopOffset }} px
                                                </div>
                                            </div>

                                            <!-- SUMMARY -->
                                            <div class="mb-3 p-2 rounded" style="background: rgba(0,0,0,0.05);">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Hadiah:</span>
                                                    <strong class="text-warning">{{ selectedPrizeLabel || '-'
                                                        }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Jumlah pemenang:</span>
                                                    <strong :class="quantity > 0 ? 'text-primary' : 'text-muted'">{{
                                                        quantity
                                                        }} orang</strong>
                                                </div>
                                                <div v-if="settingError" class="mt-1 text-danger small fw-semibold">
                                                    <i class="fa fa-exclamation-triangle me-1"></i>{{ settingError }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <button class="btn btn-alt-warning w-100" v-on:click="saveSetting"
                                                        :disabled="!!settingError">
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
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1 page-nav-btn"
                        title="Kembali ke Undian Biasa" :disabled="pageSwitching" @click="goToRegularDraw">
                        <span v-if="pageSwitching" class="page-nav-spinner" aria-hidden="true"></span>
                        <i v-else class="fa fa-fw fa-arrow-left"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout"
                        data-action="side_overlay_toggle">
                        <i class="fa fa-fw fa-stream"></i>
                    </button>
                </div>
            </div>
        </header>
        <main id="main-container" style="padding-top:unset !important">
            <div class="bg-undian" @click="trigger" :style="{ backgroundImage: `url(${currentBgImage})` }">
                <div class="bg-black-50">
                    <div class="content container-fluid undian-content">
                        <div class="undian-center" :style="{ marginTop: undianTopOffset + 'px' }">

                            <template v-if="hasSetup">
                                <div class="kec-card p-2">
                                    <table class="table table-sm table-vcenter kec-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">#</th>
                                                <th style="width: 12%;">Nomor Polisi</th>
                                                <th style="width: 5%;">Roda</th>
                                                <th style="width: 16%;">Nama Pemilik</th>
                                                <th style="width: 40%;">Alamat</th>
                                                <th style="width: 12%;">Lokasi</th>
                                                <th style="width: 14%;">Hadiah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in undianList" :key="item.id">
                                                <td>{{ index + 1 }}.</td>
                                                <td class="fw-bold text-nowrap text-primary">
                                                    {{ item.kodeWilayah }} {{ item.angka }} {{ item.kodeSeri }}
                                                </td>
                                                <template v-if="drawing">
                                                    <td class="table-fixed-col col-roda">{{ item.roda || '??' }}
                                                    </td>
                                                    <td class="table-fixed-col col-nama">{{ (item.nama || '-') }}
                                                    </td>
                                                    <td class="table-fixed-col col-alamat">{{ (item.alamat || '-')
                                                        }}</td>
                                                    <td class="table-fixed-col col-lokasi">{{ (item.lokasi || '-')
                                                        }}</td>
                                                </template>
                                                <template v-else>
                                                    <td class="table-fixed-col col-roda">{{ item.roda || '-' }}</td>
                                                    <td class="table-fixed-col col-nama">{{ (item.nama || '-') }}
                                                    </td>
                                                    <td class="table-fixed-col col-alamat">{{ (item.alamat || '-')
                                                        }}</td>
                                                    <td class="table-fixed-col col-lokasi">{{ (item.lokasi || '-')
                                                        }}</td>
                                                </template>
                                                <td class="text-primary fw-semibold">{{ item.prize?.name || '-'
                                                    }}</td>
                                            </tr>
                                            <tr v-if="undianList.length === 0">
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    Belum ada pemenang. Atur pengaturan dan mulai pengundian.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-center draw-button-wrap">
                                    <button type="button" class="btn btn-rounded btn-xl fw-semibold animated shake"
                                        id="btnDraw" :class="drawButtonClass" @click="drawHandle"
                                        :style="drawButtonStyle" :disabled="drawButtonDisabled" autofocus>
                                        {{ drawButtonLabel }}
                                    </button>
                                </div>

                                <h6 class="text-white fw-medium mb-0" v-if="!drawing">
                                    *Tekan <strong>Start Pengundian!</strong> untuk memulai mengacak undian
                                </h6>
                                <h6 class="text-white fw-medium mb-0" v-if="drawing">
                                    *Tekan <strong>Stop Pengundian!</strong> untuk berhenti mengacak undian
                                </h6>
                            </template>

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

.table-fixed-col {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
    /* sensible default, will be overridden per-col */
}

.col-roda {
    max-width: 60px;
}

.col-nama {
    max-width: 140px;
}

.col-alamat {
    max-width: 340px;
}

.col-lokasi {
    max-width: 120px;
}

.h1-custom {
    font-size: 5.571429rem;
}

.bg-black-50 {
    min-height: 100vh;
    background-color: transparent !important;
}

.bg-black-op-50 {
    background-color: rgba(0, 0, 0, .75) !important;
}

/* Konten (tabel + tombol): jarak dari atas diatur lewat undianTopOffset */
.undian-content {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 140px);
}

.undian-center {
    margin-bottom: auto;
    width: 100%;
}

/* Kartu dibuat cerah agar mudah terlihat */
.kec-card {
    background: #ffffff;
    border: 1px solid rgba(13, 110, 253, 0.18);
    border-radius: 0.6rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.30);
    color: #1f2937;
    overflow-x: auto;
}

.kec-table {
    font-size: 0.8rem;
    margin-bottom: 0;
    color: #1f2937;
}

.kec-table>thead>tr>th {
    background: #eef2ff;
    color: #3730a3;
    border-color: #dbe4ff;
    text-align: center;
    vertical-align: middle;
}

.kec-table>tbody>tr>td {
    vertical-align: middle;
    border-color: #eef0f4;
}

/* Jarak tombol start dari kartu pengundian */
.draw-button-wrap {
    margin-top: 2.5rem;
    margin-bottom: 1.5rem;
}

.tabel {
    margin-top: 0;
}

.pemenang {
    padding-top: 370px;
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
import { ref, reactive, onMounted, onUnmounted, computed, watch, inject } from "vue";
import axios from "axios";
import confetti from 'canvas-confetti';
import bgImage from '@/../images/grandprize.webp'
import bgImageInitial from '@/../images/background1-min3.webp'

export default {
    setup() {
        const appName = inject('appName');
        const drawing = ref(false);
        const shuffling = ref(false);
        const winnersReady = ref(false);
        const drawFinished = ref(false);
        const pageSwitching = ref(false);
        const shufflingTimeout = ref(null);
        const drawButtonSize = ref(Number(localStorage.getItem('grandPrizeDrawButtonSize')) || 100);

        // All Jateng plate prefixes for realistic global draw animation
        const platJateng = ['H', 'K', 'AD', 'G', 'R', 'AA'];
        const disableTrigger = ref(true);

        const quantity = ref(1);
        const selectedCategoryId = ref('');
        const selectedPrizeIds = ref([]);
        const prizeOptions = ref([]);

        const extractGram = (name) => {
            const cleaned = String(name || '').replace(',', '.');
            const match = cleaned.match(/([0-9]+(?:\.[0-9]+)?)/);
            return match ? parseFloat(match[1]) : 0;
        };

        const sortPrizeByWeight = (a, b) => {
            return extractGram(b.name) - extractGram(a.name);
        };

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

        const filteredPrizes = computed(() => {
            if (!selectedCategoryId.value) {
                return [];
            }
            return prizeOptions.value
                .filter(item => item.prize_category?.id === selectedCategoryId.value)
                .sort(sortPrizeByWeight);
        });

        const selectedPrizeNames = computed(() => {
            return selectedPrizeIds.value
                .map(id => prizeOptions.value.find(item => item.id === id))
                .filter(Boolean)
                .sort(sortPrizeByWeight)
                .map(item => item.name);
        });

        const selectedPrizeLabel = computed(() => {
            if (selectedPrizeNames.value.length === 0) return '-';
            return selectedPrizeNames.value.join(', ');
        });

        const maxQuantity = computed(() => {
            return selectedPrizeIds.value.reduce((sum, id) => {
                const prize = prizeOptions.value.find(item => item.id === id);
                return sum + (prize ? prize.available_quantity : 0);
            }, 0);
        });

        const onCategoryChange = () => {
            if (!selectedCategoryId.value) {
                selectedPrizeIds.value = [];
                quantity.value = 1;
                return;
            }

            selectedPrizeIds.value = filteredPrizes.value.map(item => item.id);
            quantity.value = maxQuantity.value || 1;
        };

        // SESUDAH

        // Watch perubahan selectedPrizeIds (deep agar array mutation terdeteksi)
        watch(selectedPrizeIds, () => {
            if (selectedPrizeIds.value.length === 0) {
                quantity.value = 1;
                return;
            }
            // Hitung ulang maxQuantity berdasarkan prize terpilih saat ini
            const newMax = selectedPrizeIds.value.reduce((sum, id) => {
                const prize = prizeOptions.value.find(item => item.id === id);
                return sum + (prize ? prize.available_quantity : 0);
            }, 0);
            // Selalu sesuaikan quantity ke stok terbaru
            quantity.value = newMax > 0 ? newMax : 1;
        }, { deep: true });

        // Watch maxQuantity untuk validasi batas atas (misalnya stok berubah dari server)
        watch(maxQuantity, (newMax) => {
            if (quantity.value > newMax) {
                quantity.value = newMax > 0 ? newMax : 1;
            }
        });

        const settingError = computed(() => {
            if (!selectedCategoryId.value) return 'Pilih kategori hadiah.';
            if (selectedPrizeIds.value.length === 0) return 'Pilih minimal 1 jenis hadiah.';
            if (!quantity.value || parseInt(quantity.value) < 1) return 'Jumlah pemenang minimal 1.';
            if (parseInt(quantity.value) > maxQuantity.value) {
                return `Jumlah (${quantity.value}) melebihi stok tersedia (${maxQuantity.value}).`;
            }
            return '';
        });

        const currentBgImage = computed(() => {
            return form.quantity > 0 ? bgImage : bgImageInitial;
        });

        const form = reactive({
            prize_ids: [],
            prizeNames: [],
            quantity: 0,
            prizeKey: '',
        });

        const undianList = ref([]);
        const winnerList = ref([]);

        const createPrizeKey = (ids = selectedPrizeIds.value) => {
            return [...ids].sort((a, b) => Number(a) - Number(b)).join('|');
        };

        const drawButtonLabel = computed(() => {
            if (shuffling.value) return 'Sedang Mengacak';
            if (drawing.value) return 'Stop Pengundian!';
            return 'Start Pengundian!';
        });

        const drawButtonClass = computed(() => {
            if (shuffling.value) return 'btn-warning';
            if (drawing.value) return 'btn-danger';
            return 'btn-success';
        });

        const drawButtonDisabled = computed(() => {
            return disableTrigger.value || shuffling.value || drawFinished.value;
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
            localStorage.setItem('grandPrizeDrawButtonSize', String(normalized));
        });

        // Jarak undian dari atas (px), tersimpan permanen di localStorage.
        const DEFAULT_UNDIAN_TOP = 24;
        const storedUndianTop = localStorage.getItem('grandPrizeUndianTopOffset');
        const undianTopOffset = ref(storedUndianTop !== null ? Number(storedUndianTop) : DEFAULT_UNDIAN_TOP);

        watch(undianTopOffset, (value) => {
            const normalized = Math.min(Math.max(Number(value) || 0, 0), 500);
            if (normalized !== value) {
                undianTopOffset.value = normalized;
                return;
            }
            localStorage.setItem('grandPrizeUndianTopOffset', String(normalized));
        });

        const resetUndianTopOffset = () => {
            undianTopOffset.value = DEFAULT_UNDIAN_TOP;
            localStorage.setItem('grandPrizeUndianTopOffset', String(DEFAULT_UNDIAN_TOP));
        };

        const clearShufflingTimeout = () => {
            if (!shufflingTimeout.value) return;
            clearTimeout(shufflingTimeout.value);
            shufflingTimeout.value = null;
        };

        const resetDrawState = () => {
            clearShufflingTimeout();
            undianList.value.forEach(row => stopDrawingRow(row));
            shuffling.value = false;
            winnersReady.value = false;
            drawing.value = false;
            drawFinished.value = false;
            winnerList.value = [];
        };

        const initUndianList = () => {
            undianList.value = [];
            for (let i = 0; i < parseInt(form.quantity); i++) {
                undianList.value.push({
                    id: i + 1,
                    kodeWilayah: 'XX',
                    angka: '9999',
                    kodeSeri: 'XX',
                    interval: {
                        wilayah: null,
                        angka: null,
                        seri: null,
                        prize: null
                    },
                    drawing: false,
                    id_kendaraan: '',
                    nama: '',
                    alamat: '',
                    roda: '',
                    lokasi: '',
                    prize: {
                        id: '',
                        name: '-'
                    }
                });
            }
        };

        const getRandomLetter = (length) => {
            const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            let letter = '';
            for (let i = 0; i < length; i++) {
                letter += alphabet[Math.floor(Math.random() * alphabet.length)];
            }
            return letter;
        };

        // Sample generators for drawing placeholders
        const sampleNames = ['Siti', 'Budi', 'Agus', 'Dewi', 'Ahmad', 'Rina', 'Slamet', 'Wati', 'Joko', 'Ika'];
        const sampleStreets = ['Merdeka', 'Sudirman', 'Pembangunan', 'Diponegoro', 'Pahlawan', 'Gajah Mada'];
        const sampleLocations = ['Kota Semarang', 'Kota Salatiga', 'Kab. Banyumas', 'Kota Surakarta', 'Kab. Kendal'];

        const getRandomRoda = () => {
            const opts = ['2', '4'];
            return opts[Math.floor(Math.random() * opts.length)];
        };

        const getRandomName = () => {
            const first = sampleNames[Math.floor(Math.random() * sampleNames.length)];
            const last = sampleNames[Math.floor(Math.random() * sampleNames.length)];
            return `${first} ${last}`;
        };

        const getRandomAddress = () => {
            const street = sampleStreets[Math.floor(Math.random() * sampleStreets.length)];
            const num = Math.floor(Math.random() * 200) + 1;
            return `Jl. ${street} No. ${num}`;
        };

        const getRandomLocation = () => {
            return sampleLocations[Math.floor(Math.random() * sampleLocations.length)];
        };

        const getRandomQuestionMarks = (min = 2, max = 10) => {
            const length = Math.floor(Math.random() * (max - min + 1)) + min;
            return '?'.repeat(length);
        };

        const getRandomPlate = () => {
            const prefix = platJateng[Math.floor(Math.random() * platJateng.length)];
            const number = String(Math.floor(Math.random() * 9999 + 1)).padStart(4, '0');
            const serie = getRandomLetter(Math.floor(Math.random() * 3 + 1));
            return `${prefix} ${number} ${serie}`;
        };

        const startDrawingRow = (row) => {
            row.drawing = true;

            row.interval.wilayah = setInterval(() => {
                row.kodeWilayah = platJateng[Math.floor(Math.random() * platJateng.length)];
            }, 75);

            row.interval.angka = setInterval(() => {
                // pad angka to 4 digits to keep width stable
                const n = Math.floor(Math.random() * 9999 + 1);
                row.angka = String(n).padStart(4, '0');
            }, 75);

            row.interval.seri = setInterval(() => {
                // fix seri length between 1-3 letters; pad to 2 for stability
                const len = Math.floor(Math.random() * 3 + 1);
                row.kodeSeri = getRandomLetter(len).padEnd(2, ' ');
            }, 75);

            row.interval.prize = setInterval(() => {
                const names = selectedPrizeNames.value;
                row.prize.name = names.length > 0 ? names[Math.floor(Math.random() * names.length)] : '-';
            }, 150);

            // randomized display fields while drawing
            row.interval.roda = setInterval(() => {
                row.roda = getRandomRoda();
            }, 100);

            row.interval.nama = setInterval(() => {
                row.nama = getRandomQuestionMarks();
            }, 120);

            row.interval.alamat = setInterval(() => {
                row.alamat = getRandomQuestionMarks(4, 14);
            }, 140);

            row.interval.lokasi = setInterval(() => {
                row.lokasi = getRandomQuestionMarks(6, 16);
            }, 200);
        };

        const stopDrawingRow = (row) => {
            row.drawing = false;

            clearInterval(row.interval.wilayah);
            clearInterval(row.interval.angka);
            clearInterval(row.interval.seri);
            clearInterval(row.interval.prize);
            clearInterval(row.interval.roda);
            clearInterval(row.interval.nama);
            clearInterval(row.interval.alamat);
            clearInterval(row.interval.lokasi);
        };

        const pickWinner = async () => {
            disableTrigger.value = true;

            try {
                const response = await axios.post("api/draw/pick", {
                    prize_ids: form.prize_ids,
                    quantity: parseInt(form.quantity),
                    is_global: true,
                    is_demo: false,
                });

                winnerList.value = response.data.data;
                winnersReady.value = true;
                getOptionsPrize();
                disableTrigger.value = false;
            } catch (error) {
                disableTrigger.value = false;
                resetDrawState();
                undianList.value.forEach(row => stopDrawingRow(row));
                alert(error.response?.data.message || 'Terjadi kesalahan saat pengundian.');
                console.error('Gagal update:', error.response?.data);
            }
        };

        const parseWinner = () => {
            const list = winnerList.value.map((item, index) => {
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
                    prize: item.prize || { name: selectedPrizeLabel.value },
                    // determine prize category level for sorting (higher = bigger prize)
                    prize_level: item.prize?.prize_category?.level ?? (
                        // fallback: try to infer from selected prize options
                        (() => {
                            const p = prizeOptions.value.find(pp => pp.id === (item.prize?.id || null));
                            return p?.prize_category?.level ?? 0;
                        })()
                    )
                };
            });

            // sort winners so higher-level (bigger) prizes appear first,
            // and keep same prize weight ordering for equal levels
            list.sort((a, b) => {
                const levelDiff = (b.prize_level || 0) - (a.prize_level || 0);
                if (levelDiff !== 0) return levelDiff;
                return extractGram(b.prize.name || '') - extractGram(a.prize.name || '');
            });

            // reindex ids
            undianList.value = list.map((it, idx) => ({ ...it, id: idx + 1 }));
        };
        const drawHandle = async () => {
            if (drawButtonDisabled.value) return;

            if (!drawing.value) {
                // start: show drawing UI immediately and set a 3s "shuffling" lock
                drawing.value = true;
                shuffling.value = true;
                winnersReady.value = false;
                undianList.value.forEach(row => startDrawingRow(row));

                const unlockStopButton = () => {
                    if (!winnersReady.value) return;
                    shuffling.value = false;
                    shufflingTimeout.value = null;
                };

                // keep button disabled and text changed for at least 3 seconds
                clearShufflingTimeout();
                shufflingTimeout.value = setTimeout(unlockStopButton, 3000);

                await pickWinner();

                if (!drawing.value) return;
                unlockStopButton();
            } else {
                // stop
                undianList.value.forEach(row => stopDrawingRow(row));
                parseWinner();
                disableTrigger.value = true;
                drawFinished.value = true;
                drawing.value = false;

                // Trigger confetti effect
                // Kiri atas
                confetti({
                    particleCount: 500,
                    angle: 60,
                    spread: 500,
                    startVelocity: 60,
                    scalar: 1.2,
                    origin: { x: 0, y: 0 }
                });

                // Kanan atas
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

        const spaceKeyHandler = (e) => {
            const isSpace = e.code === 'Space' || e.key === ' ' || e.key === 'Spacebar';
            if (!isSpace) return;
            const active = document.activeElement && document.activeElement.tagName;
            if (active === 'INPUT' || active === 'TEXTAREA' || active === 'SELECT' || document.activeElement?.isContentEditable) return;
            e.preventDefault();
            if (drawButtonDisabled.value) return;
            drawHandle();
        };

        const saveSetting = () => {
            if (settingError.value) {
                alert(settingError.value);
                return;
            }

            const nextPrizeKey = createPrizeKey();
            const prizeChanged = nextPrizeKey !== form.prizeKey;

            form.prize_ids = [...selectedPrizeIds.value];
            form.prizeNames = [...selectedPrizeNames.value];
            form.quantity = parseInt(quantity.value);
            form.prizeKey = nextPrizeKey;

            const el = document.getElementById('page-container');
            if (el) {
                el.classList.remove('side-overlay-o');
            }

            if (prizeChanged) {
                resetDrawState();
            }

            if (drawFinished.value) {
                disableTrigger.value = true;
                return;
            }

            initUndianList();
            disableTrigger.value = false;
        };

        const hasSetup = computed(() => {
            return form.prize_ids.length > 0 && form.quantity > 0;
        });

        const trigger = () => {
            // optional click trigger
        };

        const goToRegularDraw = () => {
            if (pageSwitching.value) return;
            pageSwitching.value = true;
            window.location.href = '/';
        };

        const getOptionsPrize = async () => {
            try {
                const response = await axios.get('/api/get-data/prize');
                prizeOptions.value = response.data;
            } catch (error) {
                console.error('Gagal memuat data hadiah:', error);
            }
        };

        onMounted(async () => {
            getOptionsPrize();
            window.addEventListener('keydown', spaceKeyHandler);
        });

        onUnmounted(() => {
            clearShufflingTimeout();
            undianList.value.forEach(row => stopDrawingRow(row));
            window.removeEventListener('keydown', spaceKeyHandler);
        });

        return {
            appName,
            prizeOptions,
            selectedCategoryId,
            selectedPrizeIds,
            prizeCategories,
            filteredPrizes,
            selectedPrizeLabel,
            maxQuantity,
            quantity,
            settingError,
            onCategoryChange,
            saveSetting,
            form,
            bgImage,
            hasSetup,

            undianList,
            startDrawingRow,
            stopDrawingRow,
            drawHandle,
            drawButtonLabel,
            drawButtonClass,
            drawButtonDisabled,
            drawButtonSize,
            drawButtonStyle,
            undianTopOffset,
            resetUndianTopOffset,
            goToRegularDraw,

            disableTrigger,
            trigger,
            drawing,
            shuffling,
            pageSwitching,
            currentBgImage
        };
    }
}
</script>
