<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Winner;
use Illuminate\Http\Request;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class DownloadController extends Controller
{
    public function drawPdf($id)
    {
        $draw = Draw::with(['winners.prize', 'winners.kendaraan', 'prize', 'prize_category'])->findOrFail($id);
        // determine prize label (supports single-prize draws and multi-prize draws)
        $prizeLabel = optional($draw->prize)->name;
        if (! $prizeLabel) {
            $names = $draw->winners->map(function ($w) {
                return optional($w->prize)->name;
            })->filter()->unique()->values();
            if ($names->isNotEmpty()) {
                $prizeLabel = $names->implode(', ');
            }
        }

        // fallback to prize category name if no explicit prize names found
        $categoryLabel = $draw->prize_category?->name ?? null;
        $titlePrize = $prizeLabel ?? $categoryLabel ?? '-';

        $title = config('app.name') . " - {$draw->quantity} Unit Hadiah {$titlePrize}";

        return PDF::loadView('pdf.draw', [
            'draw' => $draw,
            'prizeLabel' => $prizeLabel,
            'categoryLabel' => $categoryLabel,
        ], [], [
            'title'             => $title,
            'orientation'       => 'L',
            'default_font_size' => '8',
            'watermark'         => config('app.name'),
            'show_watermark'    => true,
        ])->stream($title . '.pdf');
    }

    public function allWinnersPdf()
    {
        $winners = Winner::with([
            'kendaraan',
            'prize.prize_category',
            'draw.prize_category'
        ])->get();

        $totalWinners = $winners->count();

        // Group by category and compute highest prize value in each group.
        $grouped = [];
        foreach ($winners as $winner) {
            $category = $winner->prize?->prize_category
                ?? $winner->draw?->prize_category
                ?? null;

            $catId = $category ? $category->id : 0;
            $catName = $category ? $category->name : 'Hadiah Lainnya';
            $catLevel = $category ? $category->level : 0;
            $prizeScore = $this->estimatePrizeValue($winner);

            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'category_level' => $catLevel,
                    'max_prize_score' => $prizeScore,
                    'winners' => collect(),
                ];
            }

            $grouped[$catId]['winners']->push($winner);
            $grouped[$catId]['max_prize_score'] = max($grouped[$catId]['max_prize_score'], $prizeScore);
        }

        $uppdOrder = array_flip(array_keys(config('uppd', [])));

        // Sort winners within each category by prize nominal highest first, then by UPPD location ID order, then by lokasi.
        foreach ($grouped as $catId => &$group) {
            $group['winners'] = $group['winners']->sort(function ($a, $b) use ($uppdOrder) {
                $scoreA = $this->estimatePrizeValue($a);
                $scoreB = $this->estimatePrizeValue($b);

                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }

                $aIdLok = $a->kendaraan?->id_lokasi;
                $bIdLok = $b->kendaraan?->id_lokasi;

                $aOrder = isset($uppdOrder[$aIdLok]) ? $uppdOrder[$aIdLok] : 999999;
                $bOrder = isset($uppdOrder[$bIdLok]) ? $uppdOrder[$bIdLok] : 999999;

                if ($aOrder !== $bOrder) {
                    return $aOrder <=> $bOrder;
                }

                $aLokasi = $a->kendaraan?->lokasi ?? '';
                $bLokasi = $b->kendaraan?->lokasi ?? '';
                if ($aLokasi !== $bLokasi) {
                    return strcmp($aLokasi, $bLokasi);
                }

                return $a->id <=> $b->id;
            })->values();
        }
        unset($group);

        // Reindex grouped categories into a numeric array and sort by category level descending,
        // then max prize value descending, and category name ascending as fallback.
        $grouped = array_values($grouped);
        usort($grouped, function ($a, $b) {
            $aLevel = $a['category_level'] ?? 0;
            $bLevel = $b['category_level'] ?? 0;
            $levelDiff = $bLevel - $aLevel;
            if ($levelDiff !== 0) {
                return $levelDiff;
            }

            $scoreDiff = $b['max_prize_score'] <=> $a['max_prize_score'];
            if ($scoreDiff !== 0) {
                return $scoreDiff;
            }

            return strcmp($a['category_name'] ?? '', $b['category_name'] ?? '');
        });

        $title = config('app.name') . ' - Daftar Seluruh Pemenang';

        return PDF::loadView('pdf.all-winners', [
            'groupedWinners' => $grouped,
            'totalWinners' => $totalWinners,
        ], [], [
            'title'             => $title,
            'orientation'       => 'L',
            'default_font_size' => '8',
            'watermark'         => config('app.name'),
            'show_watermark'    => true,
        ])->stream($title . '.pdf');
    }

    protected function estimatePrizeValue($winner): int
    {
        $prize = optional($winner)->prize;
        if (! $prize) {
            return 0;
        }

        $text = trim(($prize->name ?? '') . ' ' . ($prize->description ?? ''));
        if ($text === '') {
            return 0;
        }

        return $this->parsePrizeTextScore($text);
    }

    protected function parsePrizeTextScore(string $text): int
    {
        $score = 0;
        $normalized = str_replace([',', 'Rp', 'rp'], ['.', '', ''], $text);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Parse cash amounts like "7,5 jt", "2 jt", "2.000.000".
        if (preg_match_all('/([\d\.]+(?:[\.,]\d+)?)(?:\s*(jt|juta|rb|ribu|k))?/iu', $normalized, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = floatval(str_replace(',', '.', str_replace('.', '', $match[1])));
                $unit = isset($match[2]) ? strtolower(trim($match[2])) : '';

                if ($unit === 'jt' || $unit === 'juta') {
                    $score += intval(round($value * 1000000));
                } elseif ($unit === 'rb' || $unit === 'ribu' || $unit === 'k') {
                    $score += intval(round($value * 1000));
                } elseif ($value >= 1000) {
                    $score += intval(round($value));
                }
            }
        }

        // Parse gold weight like "5 gr" or "2,5 gr".
        if (preg_match_all('/([\d\.]+(?:[\.,]\d+)?)\s*gr/i', $normalized, $goldMatches, PREG_SET_ORDER)) {
            foreach ($goldMatches as $match) {
                $grams = floatval(str_replace(',', '.', $match[1]));
                $score += intval(round($grams * 150000));
            }
        }

        return $score;
    }
}
