<template>
    <div class="mb-3">
        <label class="form-label fw-semibold">
            <i class="fa fa-image me-1" :class="accentTextClass"></i>
            Background Halaman
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

        <!-- Mode pratinjau -->
        <div class="btn-group btn-group-sm w-100 mt-2" role="group">
            <button type="button" class="btn btn-alt-secondary pv-mode-btn" :class="{ active: mode === 'poster' }"
                @click="mode = 'poster'">
                <i class="fa fa-file-image me-1"></i> Poster
            </button>
            <button type="button" class="btn btn-alt-secondary pv-mode-btn" :class="{ active: mode === 'page' }"
                @click="mode = 'page'">
                <i class="fa fa-table me-1"></i> Halaman Undian
            </button>
        </div>

        <input ref="fileInput" type="file" class="d-none" accept="image/png,image/jpeg,image/webp,image/gif"
            @change="onFilePicked">

        <div class="d-grid gap-2 mt-2">
            <button type="button" class="btn btn-sm" :class="accentBtnClass" :disabled="!candidateBg"
                @click="applyBackground">
                <i class="fa fa-check me-1"></i> Terapkan Background
            </button>
            <button type="button" class="btn btn-sm btn-alt-secondary"
                :disabled="!hasCustom && !candidateBg" @click="resetBackground">
                <i class="fa fa-undo me-1"></i> Reset ke Default
            </button>
        </div>

        <div class="mt-2 small" :class="statusClass">
            <i class="fa me-1" :class="statusIcon"></i>{{ statusText }}
        </div>
        <div v-if="error" class="text-danger small mt-1">
            <i class="fa fa-exclamation-triangle me-1"></i>{{ error }}
        </div>
        <div class="text-muted mt-1" style="font-size: 0.7rem;">
            JPG/PNG/WebP, disarankan 1920×1080 (landscape). Gambar otomatis dikompres dan disimpan di browser ini.
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";

const MAX_FILE_BYTES = 10 * 1024 * 1024;
const MAX_STORAGE_CHARS = 3000000;
const MAX_DIMENSION = 1920;
const CANVAS_WIDTH = 1280;
const CANVAS_HEIGHT = 720;
const ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

const readAsDataUrl = (file) => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error('Gagal membaca file gambar.'));
    reader.readAsDataURL(file);
});

const loadImage = (src) => new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('Gambar tidak dapat dibaca.'));
    img.src = src;
});

export default {
    props: {
        storageKey: {
            type: String,
            required: true,
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
    emits: ['change'],
    setup(props, { emit }) {
        const frameEl = ref(null);
        const fileInput = ref(null);
        const dragging = ref(false);
        const mode = ref('page');
        const error = ref('');
        const scale = ref(0.25);

        const storedValue = () => {
            try {
                const value = localStorage.getItem(props.storageKey);
                return value && value !== 'null' ? value : '';
            } catch (e) {
                return '';
            }
        };

        const savedBg = ref(storedValue());
        const candidateBg = ref(savedBg.value);

        const hasCustom = computed(() => !!savedBg.value);
        const isDirty = computed(() => !!candidateBg.value && candidateBg.value !== savedBg.value);

        const backgroundForMode = computed(() => {
            if (candidateBg.value) return candidateBg.value;
            return mode.value === 'poster' ? props.fallbackPoster : props.fallbackPage;
        });

        const canvasStyle = computed(() => ({
            backgroundImage: backgroundForMode.value ? `url(${backgroundForMode.value})` : 'none',
            transform: `translate(-50%, -50%) scale(${scale.value})`,
        }));

        const accentBtnClass = computed(() => props.accent === 'warning' ? 'btn-alt-warning' : 'btn-alt-primary');
        const accentTextClass = computed(() => props.accent === 'warning' ? 'text-warning' : 'text-primary');

        const statusText = computed(() => {
            if (error.value) return 'Periksa pesan kesalahan di bawah.';
            if (isDirty.value) return 'Pratinjau belum diterapkan.';
            if (hasCustom.value) return 'Background custom aktif.';
            return 'Menggunakan background bawaan.';
        });

        const statusClass = computed(() => {
            if (error.value) return 'text-danger';
            if (isDirty.value) return 'text-warning';
            if (hasCustom.value) return 'text-success';
            return 'text-muted';
        });

        const statusIcon = computed(() => {
            if (error.value) return 'fa-exclamation-triangle';
            if (isDirty.value) return 'fa-pencil';
            if (hasCustom.value) return 'fa-check-circle';
            return 'fa-image';
        });

        const compressImage = async (file) => {
            const dataUrl = await readAsDataUrl(file);

            // GIF dipertahankan apa adanya agar animasi tidak hilang.
            if (file.type === 'image/gif') return dataUrl;

            const img = await loadImage(dataUrl);
            const ratio = Math.min(1, MAX_DIMENSION / Math.max(img.width, img.height));
            const width = Math.max(1, Math.round(img.width * ratio));
            const height = Math.max(1, Math.round(img.height * ratio));

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);

            let compressed = canvas.toDataURL('image/webp', 0.85);
            if (!compressed.startsWith('data:image/webp')) {
                compressed = canvas.toDataURL('image/jpeg', 0.85);
            }

            // Pakai hasil terkecil supaya hemat kuota localStorage.
            return compressed && compressed.length < dataUrl.length ? compressed : dataUrl;
        };

        const useFile = async (file) => {
            error.value = '';

            if (!file || !ALLOWED_TYPES.includes(file.type)) {
                error.value = 'Format harus JPG, PNG, WebP, atau GIF.';
                return;
            }
            if (file.size > MAX_FILE_BYTES) {
                error.value = 'Ukuran file maksimal 10 MB.';
                return;
            }

            try {
                const dataUrl = await compressImage(file);
                if (dataUrl.length > MAX_STORAGE_CHARS) {
                    error.value = 'Gambar masih terlalu besar setelah dikompres. Gunakan resolusi lebih kecil.';
                    return;
                }
                candidateBg.value = dataUrl;
            } catch (e) {
                error.value = e.message || 'Gagal memproses gambar.';
            }
        };

        const pickFile = () => {
            if (fileInput.value) fileInput.value.click();
        };

        const onFilePicked = async (event) => {
            const file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (file) await useFile(file);
        };

        const onDrop = async (event) => {
            dragging.value = false;
            const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
            if (file) await useFile(file);
        };

        const applyBackground = () => {
            error.value = '';
            if (!candidateBg.value) {
                error.value = 'Pilih gambar terlebih dahulu.';
                return;
            }

            try {
                localStorage.setItem(props.storageKey, candidateBg.value);
            } catch (e) {
                error.value = 'Penyimpanan browser penuh. Gunakan gambar lebih kecil atau reset background.';
                return;
            }

            savedBg.value = candidateBg.value;
            emit('change', savedBg.value);
        };

        const resetBackground = () => {
            try {
                localStorage.removeItem(props.storageKey);
            } catch (e) { /* abaikan */ }

            savedBg.value = '';
            candidateBg.value = '';
            error.value = '';
            if (fileInput.value) fileInput.value.value = '';
            emit('change', '');
        };

        let observer = null;

        onMounted(() => {
            if (frameEl.value && typeof ResizeObserver !== 'undefined') {
                observer = new ResizeObserver((entries) => {
                    const width = entries[0] && entries[0].contentRect ? entries[0].contentRect.width : 0;
                    if (width > 0) scale.value = width / CANVAS_WIDTH;
                });
                observer.observe(frameEl.value);
            }

            emit('change', savedBg.value);
        });

        onBeforeUnmount(() => {
            if (observer) observer.disconnect();
        });

        return {
            frameEl,
            fileInput,
            dragging,
            mode,
            error,
            candidateBg,
            hasCustom,
            isDirty,
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
