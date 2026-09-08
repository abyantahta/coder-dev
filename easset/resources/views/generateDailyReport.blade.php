{{-- {{ dd($transactions) }} --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    {{-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> --}}
    
    <title>Document</title>
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}

    <style>
        *{
            font-family: 'Times New Roman', Times, serif;
        }
        .headingRow {
            padding: 1rem;
            border-bottom-width: 1px;
        }

        .heading p {
            display: block;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-size: 0.875rem;
            /* line-height: 1.25rem; */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-weight: 700;
            /* line-height: 1; */
            opacity: 0.7;
        }

        .divTable {
            display: flex;
            overflow: scroll;
            position: relative;
            margin-top: 2rem;
            flex-direction: column;
            border-radius: 0.75rem;
            width: 100%;
            height: 100%;
            color: #374151;
            background-clip: border-box;
            background-color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .tableContent {
            width: 100%;
            min-width: max-content;
            text-align: left;
            /* table-layout: auto; */
            /* border: 2px solid black; */
            border-collapse: collapse;
            overflow: hidden;
            /* clear: both; */
            /* margin-top: 70px; */
            /* table-border */
        }

        .tableContent th,
        .tableContent td {
            border: 1px solid #222
        }

        .childRow {
            /* padding: 0.25rem; */
            /* padding-left: 1rem */
            /* border-bottom-width: 1px; */
        }

        .childRow p {
            display: block;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-size: 0.875rem;
            /* line-height: 1.25rem; */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-weight: 400;
            /* line-height: 1.5; */
        }

        .header-one {
            font-size: 1.5rem;
            /* line-height: 1.75rem; */
            margin: 0;
            padding: 0
            font-weight: bold;
        }
        .header-two {
            font-size: 1.25rem;
            /* line-height: 1.75rem; */
            margin: 0;
            padding: 0
            font-weight: bold;
        }

        .divHeader {
            /* display: flex; */
            justify-content: space-between;
            width: 100%;
            /* margin-bottom: 50px */
        }
        .floatRight{
            float: right;
            /* background-color: red; */
            /* clear: right; */
        }
        .floatLeft{
            float: left;
            /* background-color: blue; */
            /* clear: left; */
        }

        .tdTableHeader {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            border-width: 1px;
            border-style: solid;
            border-color: #222
        }

        .tableHeader {
            border-width: 1px;
            border-style: solid;
            text-align: center;
            border-collapse: collapse;
            table-layout: auto;
        }
    </style>
</head>
@php
    $transactionsDecode = json_decode($transactions);
    // dd($transactionsDecode);
@endphp

<body style="padding: 1.5rem ">
    <div class="divHeader">
        <div class="floatLeft">
            <p class="header-one" style="font-weight: 700;">AKTIVITAS STO ASSET</p>
            <p class="header-two" style="font-weight: 700;">
                PT.SANKEI DHARMA INDONESIA</p>
            <table class="" style="margin-top: 10px">
                <tr>
                    <td>Hari / Tanggal </td>
                    <td>: {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td>Kategori </td>
                    <td>: {{ $kategori }}</td>
                </tr>
            </table>
        </div>
        <div class="floatRight">
            <table style="font-size: 12px" class="tableHeader">
                <tr class="">
                    <td style="min-width: 100px" class="tdTableHeader">Admin Dept. Head</td>
                    <td style="min-width: 100px" class="tdTableHeader">{{ $divisionInCharge->jabatan?->name }}</td>
                    <td style="min-width: 100px" class="tdTableHeader">Asset Management</td>
                    {{-- <td style="min-width: 100px" class="tdTableHeader">{{ $stoAdmin['position']  }} </td> --}}
                    <td style="min-width: 100px" class="tdTableHeader">PIC</td>
                </tr>
                <tr class="">
                    <td class="tdTableHeader"><div style="height: 5rem" class=""></div></td>
                    <td class="tdTableHeader"><div style="height: 5rem" class=""></div></td>
                    <td class="tdTableHeader"><div style="height: 5rem" class=""></div></td>
                    <td class="tdTableHeader"><div style="height: 5rem" class=""></div></td>
                </tr>
                <tr>
                    <td class="tdTableHeader">Agung Samudra</td>
                    <td class="tdTableHeader">{{ $divisionInCharge['name']  }}</td>
                    {{-- <td class="tdTableHeader">{{ $stoAdmin['name'] }}</td> --}}
                    <td class="tdTableHeader">Muhammad Khoirifan</td>
                    <td class="tdTableHeader">{{ $pic['name'] }}</td>
                </tr>
            </table>
        </div>
        <div style="clear: both; background-color: #fff; height: 30px" class=""></div>
    </div>
    {{-- <h1>halo semuanya</h1> --}}
    {{-- <div class="divTable"> --}}
        {{-- <h1>halo</h1> --}}
        <table style="margin-top: 0px; text-align: center" class="tableContent">
            <thead>
                <tr>
                    <th style="width: 20px" class="headingRow">
                        No
                    </th>
                    <th class="headingRow">
                        Waktu STO
                    </th>
                    <th class="headingRow">
                        Nomor Aset
                    </th>
                    <th class="headingRow">
                        Nama Aset
                    </th>
                    <th class="headingRow">
                        Kondisi
                    </th>
                    <th class="headingRow">
                        Lokasi
                    </th>
                    <th class="headingRow">
                        Keterangan
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactionsDecode as $transaction)
                    <tr>
                        <td class="childRow">

                            {{ $loop->index + 1 }}
                        </td>
                        <td class="childRow">

                            {{ \Carbon\Carbon::parse($transaction->created_at)->setTimezone("Asia/Jakarta")->translatedFormat('H:i') }}
                            {{-- {{ $transaction->created_at }} --}}

                        </td>
                        <td class="childRow">

                            {{ $transaction->item_id->no_asset }}

                        </td>
                        <td class="childRow">

                            {{ $transaction->item_id->name }}
                        </td>
                        <td class="childRow">

                            {{ $transaction->kondisi }}

                        </td>
                        <td class="childRow">

                            {{ $transaction->location_id->location_name }}

                        </td>
                        <td class="childRow">

                            {{ $transaction->keterangan }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    {{-- </div> --}}
    {{-- </table> --}}


</body>

</html>
