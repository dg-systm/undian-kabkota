<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Services\DrawingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DrawControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     * php artisan test --filter=DrawControllerTest
     */
    public function test_example(): void
    {
        $query = new DrawingService();
        $prize = Prize::find(6);
        $samsat = [
            'Semarang I',
            'Semarang II',
            'Semarang III',
            'Salatiga',
            'Ungaran',
            'Kendal',
            'Demak'
        ];
        $query = $query->pickDraw($prize, $samsat);

        dd($query);
    }
}
