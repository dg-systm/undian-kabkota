<template>
    <div class="mb-3">
        <label class="form-label fw-semibold">
            <i class="fa fa-image me-1" :class="accentTextClass"></i>
            Background Poster & Halaman
        </label>

        <!-- Kerangka pratinjau: miniatur halaman undian dengan background kandidat -->
        <div ref="frameEl" class="bg-preview" :class="{ 'is-dragover': dragging }"
            title="Klik atau taruh gambar di sini" @click="pickFile" @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
            <div class="bg-preview-canvas" :style="canvasStyle">
                <!-- Tombol navigasi halaman (mini) -->
                <div class="pv-header">
                    <span class="pv-chip pv-chip-success"></span>
                    <span class="pv-chip pv-chip-secondary"></span>
                </div>

                <div v-if="mode === 'page'" class="pv-body" :style="previewStyle">
                    <!-- Variant undian biasa: grid kartu per kecamatan -->
                    <div v-if="variant === 'undian'" class="pv-grid">
                        <div v-for="card in 2" :key="card" class="pv-card">
                            <div class="pv-card-head">
                                <span class="pv-title"></span>
                                <span class="pv-badge"></span>
                            </div>
                            <div class="pv-table">
                                <div class="pv-row pv-row-head">
                                    <span class="pv-th"></span>
                                    <span class="pv-th"></span>
                                    <span class="pv-th"></span>
                                    <span class="pv-th"></span>
                                </div>
                                <div v-for="row in 3" :key="row" class="pv-row">
                                    <span class="pv-td pv-td-strong"></span>
                                    <span class="pv-td"></span>
                                    <span class="pv-td"></span>
                                    <span class="pv-td pv-td-prize"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Variant grand prize: satu tabel lebar -->
                    <div v-else class="pv-card pv-card-wide">
                        <div class="pv-table">
                            <div class="pv-row pv-row-head">
                                <span v-for="col in 7" :key="col" class="pv-th"></span>
                            </div>
                            <div v-for="row in 3" :key="row" class="pv-row">
                                <span v-for="col in 7" :key="col" class="pv-td"
                                    :class="{ 'pv-td-strong': col === 2, 'pv-td-prize': col === 7 }"></span>
                            </div>
                        </div>
                    </div>

                    <div class="pv-draw-wrap">
                        <span class="pv-btn" :class="variant === 'undian' ? 'pv-btn-success' : 'pv-btn-warning'">
                            Start Pengundian!
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-preview-hint">
                <i class="fa fa-hand-pointer me-1"></i> Klik / taruh gambar untuk pratinjau
            </div>
        </div>

        <!-- Pilih background yang diatur: poster atau halaman undian -->
        <div class="btn-group btn-group-sm w-100 mt-2" role="group">
            <button type="button" class="btn btn-alt-secondary pv-mode-btn" :class="{ active: mode === 'poster' }"
                @click="mode = 'poster'">
                <i class="fa fa-file-image me-1"></i> Poster
            </button>
            <button type="button" class="btn btn-alt-secondary pv-mode-btn" :class="{ active: mode === 'page' }"
                @click="mode = 'page'">
                <i class="fa fa-table me-1"></i> {{ pageLabel }}
            </button>
        </div>
        <div class="text-muted mt-1" style="font-size: 0.7rem;">
            Poster dan {{ pageLabel }} dapat memakai gambar yang berbeda.
        </div>

        <!-- Ukuran background: langsung diterapkan agar mudah disesuaikan -->
        <div class="mt-2">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-semibold mb-0" style="font-size: 0.8rem;">
                    <i class="fa fa-search-plus me-1" :class="accentTextClass"></i>
                    Ukuran Background {{ activeLabel }}
                </label>
                <button type="button" class="btn btn-xs btn-alt-secondary" :disabled="isSizeDefault"
                    @click="resetBackgroundSize">
                    Reset
                </button>
            </div>
            <div class="d-flex align-items-center gap-2 mt-1">
                <input type="range" class="form-range flex-grow-1" :min="MIN_SIZE" :max="MAX_SIZE" step="5"
                    v-model.number="activeSlot.size">
                <input type="number" class="form-control form-control-sm" style="width: 78px;" :min="MIN_SIZE"
                    :max="MAX_SIZE" step="5" v-model.number="activeSlot.size">
            </div>
            <div class="text-muted mt-1" style="font-size: 0.72rem;">
                {{ activeSlot.size }}% — perubahan ukuran langsung diterapkan ke halaman.
            </div>
        </div>

        <input ref="fileInput" type="file" class="d-none" accept="image/png,image/jpeg,image/webp,image/gif"
            @change="onFilePicked">

        <div class="d-grid gap-2 mt-2">
            <button type="button" class="btn btn-sm" :class="accentBtnClass" :disabled="!activeSlot.candidateBlob"
                @click="applyBackground">
                <i class="fa fa-check me-1"></i> Terapkan Background {{ activeLabel }}
            </button>
            <button type="button" class="btn btn-sm btn-alt-secondary"
                :disabled="!hasCustom && !activeSlot.candidateBlob && isSizeDefault" @click="resetBackground">
                <i class="fa fa-undo me-1"></i> Reset {{ activeLabel }} ke Default
            </button>
        </div>

        <div v-if="activeSlot.candidateBlob" class="text-muted mt-2" style="font-size: 0.7rem;">
            <i class="fa fa-file-image me-1"></i>{{ activeSlot.candidateBlob.name || 'gambar' }} · {{
                candidateSizeLabel }}
        </div>

        <div class="mt-2 small" :class="statusClass">
            <i class="fa me-1" :class="statusIcon"></i>{{ statusText }}
        </div>
        <div v-if="error" class="text-danger small mt-1">
            <i class="fa fa-exclamation-triangle me-1"></i>{{ error }}
        </div>
        <div class="text-muted mt-1" style="font-size: 0.7rem;">
            JPG/PNG/WebP/GIF, disarankan 1920×1080 (landscape). Gambar disimpan tanpa kompresi di browser ini.
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from "vue";

const MAX_FILE_BYTES = 25 * 1024 * 1024;
const CANVAS_WIDTH = 1280;
const CANVAS_HEIGHT = 720;
const DEFAULT_SIZE = 100;
const MIN_SIZE = 50;
const MAX_SIZE = 200;
const ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
const SLOTS = ['poster', 'page'];

// Background disimpan apa adanya (tanpa kompresi) sebagai blob di IndexedDB,
// karena localStorage tidak mampu menampung gambar beresolusi tinggi.
const DB_NAME = 'undianBackgroundStore';
const STORE_NAME = 'backgrounds';
const DB_VERSION = 1;

const openDatabase = () => new Promise((resolve, reject) => {
    if (typeof indexedDB === 'undefined') {
        reject(new Error('Browser ini tidak mendukung penyimpanan IndexedDB.'));
        return;
    }

    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = () => {
        const db = request.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
            db.createObjectStore(STORE_NAME);
        }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error || new Error('Gagal membuka penyimpanan browser.'));
});

const idbGet = async (key) => {
    const db = await openDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const request = tx.objectStore(STORE_NAME).get(key);
        request.onsuccess = () => resolve(request.result || null);
        request.onerror = () => reject(request.error || new Error('Gagal membaca gambar tersimpan.'));
        tx.oncomplete = () => db.close();
        tx.onerror = () => { db.close(); reject(tx.error || new Error('Gagal membaca gambar tersimpan.')); };
        tx.onabort = () => { db.close(); reject(tx.error || new Error('Gagal membaca gambar tersimpan.')); };
    });
};

const idbPut = async (key, blob) => {
    const db = await openDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).put(blob, key);
        tx.oncomplete = () => { db.close(); resolve(); };
        tx.onerror = () => { db.close(); reject(tx.error || new Error('Gagal menyimpan gambar.')); };
        tx.onabort = () => { db.close(); reject(tx.error || new Error('Penyimpanan gambar dibatalkan.')); };
    });
};

const idbDelete = async (key) => {
    const db = await openDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).delete(key);
        tx.oncomplete = () => { db.close(); resolve(); };
        tx.onerror = () => { db.close(); reject(tx.error || new Error('Gagal menghapus gambar.')); };
        tx.onabort = () => { db.close(); reject(tx.error || new Error('Gagal menghapus gambar.')); };
    });
};

// Konversi background lama (data URL di localStorage) ke blob untuk migrasi.
const dataUrlToBlob = (dataUrl) => {
    const [meta, base64] = String(dataUrl).split(',');
    if (!base64) throw new Error('Data gambar lama tidak valid.');

    const mime = (meta.match(/data:([^;]+)/) || [])[1] || 'image/*';
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return new Blob([bytes], { type: mime });
};

const formatBytes = (bytes) => {
    const size = Number(bytes) || 0;
    if (size >= 1024 * 1024) return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    if (size >= 1024) return `${Math.round(size / 1024)} KB`;
    return `${size} B`;
};

export default {
    props: {
        storageKey: {
            type: String,
            required: true,
        },
        posterStorageKey: {
            type: String,
            default: '',
        },
        variant: {
            type: String,
            default: 'undian',
        },
        accent: {
            type: String,
            default: 'primary',
        },
        previewStyle: {
            type: Object,
            default: () => ({}),
        },
        fallbackPoster: {
            type: String,
            default: '',
        },
        fallbackPage: {
            type: String,
            default: '',
        },
    },
    emits: ['change', 'size-change'],
    setup(props, { emit }) {
        const frameEl = ref(null);
        const fileInput = ref(null);
        const dragging = ref(false);
        const mode = ref('page');
        const error = ref('');
        const scale = ref(0.25);
        const loading = ref(true);

        const pageLabel = computed(() => (props.variant === 'grand-prize' ? 'Halaman Grand Prize' : 'Halaman Undian'));
        const activeLabel = computed(() => (mode.value === 'poster' ? 'Poster' : pageLabel.value));

        // Poster dan halaman undian memakai gambar + ukuran yang terpisah.
        const slotKeys = computed(() => ({
            poster: props.posterStorageKey || `${props.storageKey}Poster`,
            page: props.storageKey,
        }));

        const normalizeSize = (value) => {
            const number = Number(value);
            if (Number.isNaN(number)) return DEFAULT_SIZE;
            return Math.min(Math.max(Math.round(number), MIN_SIZE), MAX_SIZE);
        };

        const readStoredSize = (key) => {
            try {
                const raw = localStorage.getItem(`${key}Size`);
                return raw !== null && raw !== '' ? normalizeSize(raw) : DEFAULT_SIZE;
            } catch (e) {
                return DEFAULT_SIZE;
            }
        };

        const createSlot = (imageKey) => ({
            savedBlob: null,
            savedUrl: '',
            candidateBlob: null,
            candidateUrl: '',
            size: readStoredSize(imageKey),
        });

        // Background per slot memakai blob + object URL, tanpa kompresi
        // agar kualitas gambar asli tetap terjaga.
        const slotState = reactive({
            poster: createSlot(slotKeys.value.poster),
            page: createSlot(slotKeys.value.page),
        });

        const activeSlot = computed(() => slotState[mode.value]);

        const hasCustom = computed(() => !!activeSlot.value.savedUrl);
        const isDirty = computed(() => {
            const slot = activeSlot.value;
            return !!slot.candidateBlob && slot.candidateBlob !== slot.savedBlob;
        });
        const isSizeDefault = computed(() => activeSlot.value.size === DEFAULT_SIZE);
        const candidateSizeLabel = computed(() => {
            const blob = activeSlot.value.candidateBlob;
            return blob ? formatBytes(blob.size) : '';
        });

        const backgroundForMode = computed(() => {
            const slot = activeSlot.value;
            const custom = slot.candidateUrl || slot.savedUrl;
            if (custom) return custom;
            return mode.value === 'poster' ? props.fallbackPoster : props.fallbackPage;
        });

        const canvasStyle = computed(() => ({
            backgroundImage: backgroundForMode.value ? `url(${backgroundForMode.value})` : 'none',
            backgroundSize: `${activeSlot.value.size}%`,
            transform: `translate(-50%, -50%) scale(${scale.value})`,
        }));

        // Ukuran tiap background (poster & halaman) disimpan terpisah.
        SLOTS.forEach((slot) => {
            watch(() => slotState[slot].size, (value) => {
                const normalized = normalizeSize(value);
                if (normalized !== value) {
                    slotState[slot].size = normalized;
                    return;
                }
                try {
                    localStorage.setItem(`${slotKeys.value[slot]}Size`, String(normalized));
                } catch (e) { /* abaikan */ }
                emit('size-change', { mode: slot, size: normalized });
            });
        });

        const resetBackgroundSize = () => {
            activeSlot.value.size = DEFAULT_SIZE;
        };

        const accentBtnClass = computed(() => props.accent === 'warning' ? 'btn-alt-warning' : 'btn-alt-primary');
        const accentTextClass = computed(() => props.accent === 'warning' ? 'text-warning' : 'text-primary');

        const statusText = computed(() => {
            if (error.value) return 'Periksa pesan kesalahan di bawah.';
            if (loading.value) return 'Memuat background tersimpan...';
            if (isDirty.value) return `Background ${activeLabel.value} belum diterapkan.`;
            if (hasCustom.value) return `Background ${activeLabel.value} custom aktif.`;
            return `Menggunakan background bawaan (${activeLabel.value}).`;
        });

        const statusClass = computed(() => {
            if (error.value) return 'text-danger';
            if (loading.value) return 'text-muted';
            if (isDirty.value) return 'text-warning';
            if (hasCustom.value) return 'text-success';
            return 'text-muted';
        });

        const statusIcon = computed(() => {
            if (error.value) return 'fa-exclamation-triangle';
            if (loading.value) return 'fa-spinner fa-spin';
            if (isDirty.value) return 'fa-pencil';
            if (hasCustom.value) return 'fa-check-circle';
            return 'fa-image';
        });

        const revokeUrl = (url) => {
            if (url && url.startsWith('blob:')) {
                try {
                    URL.revokeObjectURL(url);
                } catch (e) { /* abaikan */ }
            }
        };

        const setSavedBlob = (slot, blob) => {
            const state = slotState[slot];
            revokeUrl(state.savedUrl);
            state.savedBlob = blob || null;
            state.savedUrl = blob ? URL.createObjectURL(blob) : '';
        };

        const setCandidateBlob = (slot, blob) => {
            const state = slotState[slot];
            revokeUrl(state.candidateUrl);
            state.candidateBlob = blob || null;
            state.candidateUrl = blob ? URL.createObjectURL(blob) : '';
        };

        const useFile = (file) => {
            error.value = '';

            if (!file || !ALLOWED_TYPES.includes(file.type)) {
                error.value = 'Format harus JPG, PNG, WebP, atau GIF.';
                return;
            }
            if (file.size > MAX_FILE_BYTES) {
                error.value = 'Ukuran file maksimal 25 MB.';
                return;
            }

            // Tanpa kompresi: file asli dipakai apa adanya agar kualitas tetap.
            setCandidateBlob(mode.value, file);
        };

        const pickFile = () => {
            if (fileInput.value) fileInput.value.click();
        };

        const onFilePicked = (event) => {
            const file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (file) useFile(file);
        };

        const onDrop = (event) => {
            dragging.value = false;
            const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
            if (file) useFile(file);
        };

        const applyBackground = async () => {
            error.value = '';
            const slot = mode.value;
            const state = slotState[slot];
            if (!state.candidateBlob) {
                error.value = 'Pilih gambar terlebih dahulu.';
                return;
            }

            try {
                await idbPut(slotKeys.value[slot], state.candidateBlob);
            } catch (e) {
                error.value = 'Gagal menyimpan gambar di browser ini. Coba ulangi atau gunakan gambar lain.';
                return;
            }

            setSavedBlob(slot, state.candidateBlob);
            emit('change', { mode: slot, url: state.savedUrl });
        };

        const resetBackground = async () => {
            const slot = mode.value;
            const state = slotState[slot];

            try {
                await idbDelete(slotKeys.value[slot]);
            } catch (e) { /* abaikan */ }

            // Bersihkan juga data lama (sebelum memakai IndexedDB).
            if (slot === 'page') {
                try {
                    localStorage.removeItem(props.storageKey);
                } catch (e) { /* abaikan */ }
            }

            setCandidateBlob(slot, null);
            setSavedBlob(slot, null);
            error.value = '';
            state.size = DEFAULT_SIZE;
            if (fileInput.value) fileInput.value.value = '';
            emit('change', { mode: slot, url: '' });
        };

        // Background dari versi lama (data URL di localStorage) dipindah ke IndexedDB
        // dan dipakai untuk Poster + Halaman sekaligus agar tampilan tidak berubah.
        const migrateLegacyBackground = async () => {
            let legacy = '';
            try {
                legacy = localStorage.getItem(props.storageKey) || '';
            } catch (e) {
                legacy = '';
            }
            if (!legacy || !legacy.startsWith('data:image')) return;

            try {
                const blob = dataUrlToBlob(legacy);
                await idbPut(slotKeys.value.page, blob);
                setSavedBlob('page', blob);

                if (!slotState.poster.savedBlob) {
                    await idbPut(slotKeys.value.poster, blob);
                    setSavedBlob('poster', blob);

                    // Samakan ukuran poster dengan ukuran lama (sekali saat migrasi).
                    if (slotState.poster.size === DEFAULT_SIZE && slotState.page.size !== DEFAULT_SIZE) {
                        slotState.poster.size = slotState.page.size;
                    }
                }

                localStorage.removeItem(props.storageKey);
            } catch (e) {
                // Gagal migrasi: gambar lama tetap ditampilkan agar tidak hilang.
                slotState.page.savedUrl = legacy;
            }
        };

        const loadSlot = async (slot) => {
            try {
                const blob = await idbGet(slotKeys.value[slot]);
                if (blob) {
                    setSavedBlob(slot, blob);
                    return;
                }
            } catch (e) {
                error.value = 'Gagal memuat background tersimpan.';
                return;
            }

            // Slot halaman memakai kunci lama sebagai sumber migrasi.
            if (slot === 'page') {
                await migrateLegacyBackground();
            }
        };

        let observer = null;

        onMounted(async () => {
            if (frameEl.value && typeof ResizeObserver !== 'undefined') {
                observer = new ResizeObserver((entries) => {
                    const width = entries[0] && entries[0].contentRect ? entries[0].contentRect.width : 0;
                    if (width > 0) scale.value = width / CANVAS_WIDTH;
                });
                observer.observe(frameEl.value);
            }

            await Promise.all(SLOTS.map(loadSlot));
            loading.value = false;

            SLOTS.forEach((slot) => {
                emit('change', { mode: slot, url: slotState[slot].savedUrl });
                emit('size-change', { mode: slot, size: slotState[slot].size });
            });
        });

        onBeforeUnmount(() => {
            if (observer) observer.disconnect();
            SLOTS.forEach((slot) => {
                revokeUrl(slotState[slot].candidateUrl);
                revokeUrl(slotState[slot].savedUrl);
            });
        });

        return {
            frameEl,
            fileInput,
            dragging,
            mode,
            error,
            activeSlot,
            activeLabel,
            pageLabel,
            candidateSizeLabel,
            hasCustom,
            isDirty,
            isSizeDefault,
            canvasStyle,
            accentBtnClass,
            accentTextClass,
            statusText,
            statusClass,
            statusIcon,
            pickFile,
            onFilePicked,
            onDrop,
            applyBackground,
            resetBackground,
            resetBackgroundSize,
            DEFAULT_SIZE,
            MIN_SIZE,
            MAX_SIZE,
        };
    },
};
</script>

<style scoped>
/* ==== Kerangka pratinjau ==== */
.bg-preview {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    border-radius: 0.5rem;
    border: 1px solid rgba(0, 0, 0, 0.15);
    background: #0b1220;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.bg-preview.is-dragover {
    border-color: #3f9ce8;
    box-shadow: 0 0 0 3px rgba(63, 156, 232, 0.25);
}

.bg-preview-canvas {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1280px;
    height: 720px;
    transform-origin: center center;
    background-color: #0b1220;
    background-position: center center;
    background-repeat: no-repeat;
    background-size: cover;
    pointer-events: none;
    user-select: none;
}

.bg-preview-hint {
    position: absolute;
    left: 0.5rem;
    bottom: 0.5rem;
    padding: 0.2rem 0.45rem;
    border-radius: 0.35rem;
    background: rgba(0, 0, 0, 0.55);
    color: #fff;
    font-size: 0.68rem;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.bg-preview:hover .bg-preview-hint {
    opacity: 1;
}

.pv-mode-btn.active {
    background-color: #3f9ce8;
    border-color: #3f9ce8;
    color: #ffffff;
}

/* ==== Miniatur halaman di dalam kerangka ==== */
.pv-header {
    position: absolute;
    top: 18px;
    right: 22px;
    display: flex;
    gap: 10px;
}

.pv-chip {
    display: block;
    width: 46px;
    height: 38px;
    border-radius: 8px;
}

.pv-chip-success {
    background: rgba(34, 197, 94, 0.95);
}

.pv-chip-secondary {
    background: rgba(255, 255, 255, 0.85);
}

.pv-body {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 28px;
    padding: 84px 110px 56px;
}

.pv-grid {
    display: flex;
    gap: 26px;
    justify-content: center;
    align-items: stretch;
}

.pv-card {
    flex: 0 0 calc(50% - 13px);
    padding: 14px;
    border-radius: 12px;
    border: 2px solid var(--kec-border, #e5e7eb);
    background: var(--kec-card-bg, #ffffff);
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.35);
}

.pv-card-wide {
    flex: 0 0 auto;
}

.pv-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 10px;
    margin-bottom: 10px;
    border-bottom: 2px solid var(--kec-border, #e5e7eb);
}

.pv-title {
    display: block;
    width: 170px;
    height: 12px;
    border-radius: 4px;
    background: var(--kec-title-color, #0d6efd);
    opacity: 0.75;
}

.pv-badge {
    display: block;
    width: 84px;
    height: 20px;
    border-radius: 10px;
    background: linear-gradient(90deg, #2563eb, #3b82f6);
}

.pv-table {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.pv-row {
    display: flex;
    gap: 8px;
}

.pv-th,
.pv-td {
    display: block;
    height: 11px;
    border-radius: 3px;
}

.pv-row-head .pv-th {
    height: 20px;
    background: var(--kec-th-bg, #eef2ff);
    border: 1px solid var(--kec-border, #dbe4ff);
}

.pv-td {
    background: var(--kec-td-bg, #ffffff);
    border: 1px solid var(--kec-border, #eef0f4);
}

.pv-td-strong {
    background: var(--kec-td-color, #1f2937);
}

.pv-td-prize {
    background: var(--kec-prize-color, #0d6efd);
}

.pv-row> :nth-child(1) {
    flex: 0 0 10%;
}

.pv-row> :nth-child(2) {
    flex: 0 0 26%;
}

.pv-row> :nth-child(3) {
    flex: 1 1 auto;
}

.pv-row> :nth-child(4) {
    flex: 0 0 24%;
}

.pv-row> :nth-child(5) {
    flex: 0 0 10%;
}

.pv-row> :nth-child(6) {
    flex: 0 0 16%;
}

.pv-row> :nth-child(7) {
    flex: 0 0 14%;
}

.pv-draw-wrap {
    display: flex;
    justify-content: center;
    margin-top: 6px;
}

.pv-btn {
    padding: 16px 42px;
    border-radius: 999px;
    font-size: 30px;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35);
}

.pv-btn-success {
    background: #22c55e;
}

.pv-btn-warning {
    background: #f59e0b;
}
</style>
