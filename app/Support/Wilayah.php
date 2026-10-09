<?php

namespace App\Support;

/**
 * Helper untuk membaca mapping wilayah dari config/coordinator.php.
 *
 * Struktur asal: Plat -> Samsat -> Kota/Kabupaten -> Kecamatan -> Kelurahan.
 * Helper ini menggabungkan data per Kabupaten/Kota (case-insensitive).
 *
 * Catatan penting:
 * - Samsat Pembantu (punya 'induk') sering menduplikasi kecamatan/kelurahan
 *   dari Samsat induknya dengan ID berbeda. Maka penghitungan dilakukan dengan
 *   deduplikasi berdasarkan NAMA:
 *     - Kecamatan : nama kecamatan
 *     - Kelurahan : nama kecamatan + nama kelurahan
 * - 'id_lokasi' setiap kecamatan diambil dari Samsat pertama yang memuatnya
 *   (diutamakan Samsat induk, karena urutan mapping menempatkan induk lebih dulu).
 */
class Wilayah
{
    /**
     * Seluruh mapping dari config/coordinator.php.
     */
    public static function mapping(): array
    {
        return config('coordinator', []);
    }

    /**
     * Daftar unik Kabupaten/Kota (terurut abjad).
     *
     * @return array<int, string>
     */
    public static function kabkotaList(): array
    {
        $list = [];

        foreach (static::mapping() as $info) {
            foreach (($info['samsat'] ?? []) as $samsat) {
                $kota = $samsat['kota'] ?? null;

                if ($kota && !in_array($kota, $list, true)) {
                    $list[] = $kota;
                }
            }
        }

        sort($list);

        return $list;
    }

    /**
     * Cari data lengkap sebuah Kabupaten/Kota (case-insensitive).
     *
     * @return array{
     *     name: string,
     *     found: bool,
     *     plat: ?string,
     *     label: ?string,
     *     samsat: array,
     *     samsat_ids: array<int, int>,
     *     kecamatan: array<int, array{id: string, nama: string}>,
     *     kecamatan_samsat: array<string, int>,
     *     kecamatan_count: int,
     *     kelurahan_count: int
     * }
     */
    public static function find(?string $kabkota): array
    {
        $needle = trim((string) $kabkota);

        $result = [
            'name'             => $needle,
            'found'            => false,
            'plat'             => null,
            'label'            => null,
            'samsat'           => [],
            'samsat_ids'       => [],
            'kecamatan'        => [],
            'kecamatan_samsat' => [],
            'kecamatan_count'  => 0,
            'kelurahan_count'  => 0,
        ];

        if ($needle === '') {
            return $result;
        }

        $kecamatanByName = []; // nama (normal) => ['id' =>, 'nama' =>, 'samsat_id' =>]
        $kelurahanSeen = [];   // "kecamatan|kelurahan" (normal) => true

        foreach (static::mapping() as $plat => $info) {
            foreach (($info['samsat'] ?? []) as $samsatName => $samsat) {
                if (strcasecmp((string) ($samsat['kota'] ?? ''), $needle) !== 0) {
                    continue;
                }

                $result['found'] = true;
                $result['plat'] = $plat;
                $result['label'] = $info['label'] ?? null;
                $result['samsat'][$samsatName] = $samsat;

                $idLokasi = isset($samsat['id_lokasi']) ? (int) $samsat['id_lokasi'] : null;

                if ($idLokasi !== null && !in_array($idLokasi, $result['samsat_ids'], true)) {
                    $result['samsat_ids'][] = $idLokasi;
                }

                foreach (($samsat['kecamatan'] ?? []) as $kecId => $kec) {
                    $kecId = (string) $kecId;
                    $kecName = $kec['nama'] ?? $kecId;
                    $kecNorm = static::normalize($kecName);

                    // Kecamatan: dedupe berdasarkan nama (Samsat Pembantu bisa
                    // mengulang kecamatan yang sama dengan ID berbeda).
                    if (!isset($kecamatanByName[$kecNorm])) {
                        $kecamatanByName[$kecNorm] = [
                            'id'        => $kecId,
                            'nama'      => $kecName,
                            'samsat_id' => $idLokasi,
                        ];

                        if ($idLokasi !== null) {
                            $result['kecamatan_samsat'][$kecId] = $idLokasi;
                        }
                    }

                    foreach (($kec['kelurahan'] ?? []) as $kelId => $kelName) {
                        $key = $kecNorm . '|' . static::normalize($kelName);
                        $kelurahanSeen[$key] = true;
                    }
                }
            }
        }

        // Susun daftar kecamatan terurut berdasarkan id.
        $kecamatan = array_values($kecamatanByName);
        usort($kecamatan, fn ($a, $b) => strcmp((string) $a['id'], (string) $b['id']));

        $result['kecamatan'] = $kecamatan;
        $result['kecamatan_count'] = count($kecamatan);
        $result['kelurahan_count'] = count($kelurahanSeen);

        return $result;
    }

    /**
     * Normalisasi teks untuk keperluan deduplikasi.
     */
    protected static function normalize(?string $value): string
    {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }
}
