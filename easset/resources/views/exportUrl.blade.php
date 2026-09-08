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
        // $transactionsDecode = json_decode($transactions);
    @endphp;
    <table>
    <thead>
        <tr>
            <th>No</th>
            <th>Year</th>
            <th>No Asset</th>
            <th>Nama Asset</th>
            <th>Url</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>{{ ($loop->index)+1 }}</td>
            <td>{{ Carbon\Carbon::parse($item->service_date)->year }}</td>
            <td>{{ $item->no_asset }}</td>
            <td>{{ $item->name }}</td>
            <td>https://e-asset.sankei-dharma.co.id/items/{{ $item->encrypted_no_asset }}_{{$item->no_asset}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
