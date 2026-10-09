<div>
    <h1>{{ config('app.name') }}</h1>
    <h3>Daftar Seluruh Pemenang Undian</h3>
    <hr>

    @php $globalIndex = 1; @endphp

    @foreach ($groupedWinners as $group)
    @if (!$loop->first)
    <pagebreak />
    @endif
    <div style="margin-bottom: 20px;">
        <table width="100%">
            <thead>
                <tr>
                    <td width="15%">Kategori Hadiah</td>
                    <td>: {{ $group['category_name'] }}</td>
                </tr>
                <tr>
                    <td>Jumlah Pemenang</td>
                    <td>: {{ count($group['winners']) }}</td>
                </tr>
            </thead>
        </table>

        <table border="1" cellpadding="4" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th align="left">#</th>
                    <th align="left">Nomor Polisi</th>
                    <th align="left">Roda</th>
                    <th align="left">Nama Pemilik</th>
                    <th align="left">Alamat</th>
                    <th align="left">Lokasi</th>
                    <th align="left" width="10%">Hadiah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['winners'] as $item)
                <tr>
                    <td>{{ $globalIndex++ }}.</td>
                    <td>{{ $item->kendaraan?->masked_no_polisi ?? '-' }}</td>
                    <td>{{ $item->kendaraan?->roda ?? '-' }}</td>
                    <td>{{ $item->kendaraan?->nama ?? '-' }}</td>
                    <td>{{ $item->kendaraan?->alamat ?? '-' }}</td>
                    <td>{{ $item->kendaraan?->lokasi ?? '-' }}</td>
                    <td>{{ optional($item->prize)->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @endforeach

    <br>
    <table width="100%">
        <tr>
            <td><strong>Total Pemenang: {{ $totalWinners }}</strong></td>
        </tr>
    </table>
    <small><i>dicetak pada: {{ date('d/m/Y H:i') }}</i></small>

    <br><br>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" colspan="3" style="padding-bottom: 16px;">
                <strong>Mengetahui dan Menyetujui:</strong>
            </td>
        </tr>
        <tr>
            @foreach(config('pdf.signatures', []) as $signature)
            <td align="center" width="33%" style="padding: 0 10px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" style="font-size: 10px;">{{ $signature['label'] ?? '' }}</td>
                    </tr>
                    <td align="center" style="height: 120px; vertical-align: bottom;">
                        &nbsp;
                    </td>
                    <tr>
                        <td align="center" style="padding-top: 4px; font-size: 10px;">({{ $signature['name'] ?? '________________________' }})</td>
                    </tr>
                </table>
            </td>
            @endforeach
        </tr>
    </table>
</div>