{{-- {{ dd($transactions) }} --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    @php
        $transactionsDecode = json_decode($transactions);
        // dd($transactionsDecode)
    @endphp;
    <table>
    <thead>
        <tr>
            <th>No</th>
            <th>Date</th>
            <th>Nomor Aset</th>
            <th>Nama Aset</th>
            <th>Kategori</th>
            <th>Kondisi</th>
            <th>Lokasi</th>
            <th>PIC</th>
            <th>Created By</th>
            <th>Image</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($transactionsDecode as $transaction)
        <tr>
            <td>{{ ($loop->index)+1 }}</td>
            <td>{{ \Carbon\Carbon::parse($transaction->created_at)->setTimezone("Asia/Jakarta")}}</td>
            <td>{{ $transaction->item_id->no_asset }}</td>
            <td>{{ $transaction->item_id->name }}</td>
            <td>{{ $transaction->item_id->category_id->name }}</td>
            <td>{{ $transaction->kondisi }}</td>
            <td>{{ $transaction->lokasi }}</td>
            <td>{{ $transaction->pic->name }}</td>
            <td>{{ $transaction->created_by->name }}</td>
            {{-- @if (isset($transaction->c))
                
            @endif --}}
            <td>
                <img src="{{ url($transaction->image_path) }}" alt="">
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
