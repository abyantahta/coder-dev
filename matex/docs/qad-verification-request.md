# Permintaan ke Admin QAD/Progress — Receiving Silent No-Op

## STATUS: RESOLVED (2026-09-21)

Root cause ditemukan lewat testing manual langsung (bukan dari jawaban admin QAD): masalahnya
ada di **struktur payload**, bukan kredensial/UOM/lot-tracking seperti hipotesis-hipotesis di
bawah. Payload lama meniru struktur project `warehouse` (nested `<receiptDetail>`, ada
`<operation>`, tanpa `<site>`/`<location>` per line) — struktur itu memang tidak cocok untuk PO
yang dibuat langsung lewat `SDI_BuatPO` (beda dari PO `warehouse` yang lahir dari alur
Requisition→Approval).

Struktur yang benar (flat `<lineDetail>` dengan `site`/`location`/`multiEntry` langsung di
dalamnya, tanpa `<operation>`, plus `<yn>true</yn><yn1>true</yn1>` di akhir) sudah diuji ulang
dan qty received **benar-benar berubah** di QAD. Lihat implementasi final di
`app/Services/Qad/QadReceivingService.php`.

Dokumen di bawah ini dibiarkan sebagai catatan historis investigasi — tidak perlu dikirim ke
admin QAD/Progress lagi.

---

## RINGKASAN MASALAH UTAMA (baca ini dulu)

`receivePurchaseOrder` (`SDI_eKanbanGR`) selalu membalas `<result>success</result>` bersih
(tanpa warning/error) untuk PO yang dibuat lewat `maintainPurchaseOrder` (`SDI_BuatPO`), TAPI
qty received di PO **tidak pernah benar-benar berubah** di QAD — sudah dites berkali-kali
(PO026787, PO026790, PO026793 dengan variasi kredensial user & UOM, semuanya "success" di API
tapi 0 di QAD).

**Hipotesis kami:** `SDI_eKanbanGR` kemungkinan dirancang khusus untuk PO yang lahir dari alur
Requisition → Approval → auto-generate PO (seperti project `warehouse`), bukan untuk PO yang
dibuat langsung lewat `SDI_BuatPO`. Dua program ini mungkin tidak pernah didesain/dites untuk
saling terhubung.

**Yang paling membantu:** tolong cek langsung source code Progress ABL untuk `SDI_BuatPO` dan
`SDI_eKanbanGR` (atau query tabel `pod_mstr` untuk PO test di bawah), untuk lihat kenapa
`receivePurchaseOrder` tidak benar-benar mem-post qty untuk PO jenis ini.

PO test yang bisa dipakai untuk debug (semuanya "success" di API tapi 0 di QAD):
- `PO026787` — part `MTPLT10019`, qty 50, line 1
- `PO026790` — part `MSPLT09001`, qty 25, line 1, user `mfg`
- `PO026793` — part `MSPLT05003`, qty 12, line 1, UOM KG (dikirim eksplisit di `<receiptUm>`)

**Environment:** `qadeesdi.site:24079` — domain `7000`. Ini environment **TEST** (dikonfirmasi
terpisah dari `qadeesdi.site:25079` yang production) — mohon pastikan admin cek domain 7000 di
instance test, bukan production.

### Contoh raw request/response — PO026790

Request (`receivePurchaseOrder`, sebelum perbaikan UOM — belum ada `<receiptUm>` di percobaan ini):

```xml
<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
<soapenv:Header>
<wsa:Action/>
<wsa:To>urn:services-qad-com:SDI_eKanbanGR</wsa:To>
<wsa:MessageID>urn:services-qad-com::SDI_eKanbanGR</wsa:MessageID>
<wsa:ReferenceParameters>
<qcom:suppressResponseDetail>false</qcom:suppressResponseDetail>
</wsa:ReferenceParameters>
<wsa:ReplyTo>
<wsa:Address>urn:services-qad-com:</wsa:Address>
</wsa:ReplyTo>
</soapenv:Header>
<soapenv:Body>
<receivePurchaseOrder>
<qcom:dsSessionContext>
<qcom:ttContext><qcom:propertyQualifier>QAD</qcom:propertyQualifier><qcom:propertyName>domain</qcom:propertyName><qcom:propertyValue>7000</qcom:propertyValue></qcom:ttContext>
<qcom:ttContext><qcom:propertyQualifier>QAD</qcom:propertyQualifier><qcom:propertyName>receiver</qcom:propertyName><qcom:propertyValue>SDI_eKanbanGR</qcom:propertyValue></qcom:ttContext>
<qcom:ttContext><qcom:propertyQualifier>QAD</qcom:propertyQualifier><qcom:propertyName>scopeTransaction</qcom:propertyName><qcom:propertyValue>false</qcom:propertyValue></qcom:ttContext>
<qcom:ttContext><qcom:propertyQualifier>QAD</qcom:propertyQualifier><qcom:propertyName>version</qcom:propertyName><qcom:propertyValue>ERP3_3</qcom:propertyValue></qcom:ttContext>
<qcom:ttContext><qcom:propertyQualifier>QAD</qcom:propertyQualifier><qcom:propertyName>mnemonicsRaw</qcom:propertyName><qcom:propertyValue>false</qcom:propertyValue></qcom:ttContext>
<qcom:ttContext><qcom:propertyQualifier>QAD</qcom:propertyQualifier><qcom:propertyName>username</qcom:propertyName><qcom:propertyValue>mfg</qcom:propertyValue></qcom:ttContext>
<qcom:ttContext><qcom:propertyQualifier>QAD</qcom:propertyQualifier><qcom:propertyName>password</qcom:propertyName><qcom:propertyValue>******</qcom:propertyValue></qcom:ttContext>
</qcom:dsSessionContext>
<dsPurchaseOrderReceive>
<purchaseOrderReceive>
<operation>A</operation>
<ordernum>PO026790</ordernum>
<effDate>2026-09-21</effDate>
<move>true</move>
<fillAll>false</fillAll>
<lineDetail>
<ordernum>PO026790</ordernum>
<line>1</line>
<receiptDetail>
<ordernum>PO026790</ordernum>
<line>1</line>
<lotserialQty>25.00000</lotserialQty>
</receiptDetail>
</lineDetail>
</purchaseOrderReceive>
</dsPurchaseOrderReceive>
</receivePurchaseOrder>
</soapenv:Body>
</soapenv:Envelope>
```

Response (real, dari QAD — `result: success`, tidak ada `dsExceptions` sama sekali):

```xml
<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><soapenv:Header><wsa:To soapenv:actor="" soapenv:mustUnderstand="0" xmlns:wsa="http://www.w3.org/2005/08/addressing">urn:services-qad-com:</wsa:To><wsa:Action soapenv:actor="" soapenv:mustUnderstand="0" xmlns:wsa="http://www.w3.org/2005/08/addressing"></wsa:Action><wsa:MessageID soapenv:actor="" soapenv:mustUnderstand="0" xmlns:wsa="http://www.w3.org/2005/08/addressing">urn:messages-qad-com:2026-09-21T13:47:14+0700</wsa:MessageID><wsa:RelatesTo soapenv:actor="" soapenv:mustUnderstand="0" xmlns:wsa="http://www.w3.org/2005/08/addressing">urn:services-qad-com::SDI_eKanbanGR</wsa:RelatesTo></soapenv:Header><soapenv:Body><ns1:receivePurchaseOrderResponse xmlns="urn:schemas-qad-com:xml-services" xmlns:ns1="urn:schemas-qad-com:xml-services"><ns1:result>success</ns1:result><ns2:dsSessionContext xmlns:ns2="urn:schemas-qad-com:xml-services:common"><ns2:ttContext><ns2:propertyQualifier>QAD</ns2:propertyQualifier><ns2:propertyName>domain</ns2:propertyName><ns2:propertyValue>7000</ns2:propertyValue></ns2:ttContext><ns2:ttContext><ns2:propertyQualifier>QAD</ns2:propertyQualifier><ns2:propertyName>receiver</ns2:propertyName><ns2:propertyValue>SDI_eKanbanGR</ns2:propertyValue></ns2:ttContext><ns2:ttContext><ns2:propertyQualifier>QAD</ns2:propertyQualifier><ns2:propertyName>scopeTransaction</ns2:propertyName><ns2:propertyValue>false</ns2:propertyValue></ns2:ttContext><ns2:ttContext><ns2:propertyQualifier>QAD</ns2:propertyQualifier><ns2:propertyName>version</ns2:propertyName><ns2:propertyValue>ERP3_3</ns2:propertyValue></ns2:ttContext><ns2:ttContext><ns2:propertyQualifier>QAD</ns2:propertyQualifier><ns2:propertyName>mnemonicsRaw</ns2:propertyName><ns2:propertyValue>false</ns2:propertyValue></ns2:ttContext><ns2:ttContext><ns2:propertyQualifier>QAD</ns2:propertyQualifier><ns2:propertyName>username</ns2:propertyName><ns2:propertyValue>mfg</ns2:propertyValue></ns2:ttContext><ns2:ttContext><ns2:propertyQualifier>QAD</ns2:propertyQualifier><ns2:propertyName>password</ns2:propertyName><ns2:propertyValue>******</ns2:propertyValue></ns2:ttContext></ns2:dsSessionContext><ns1:dsPurchaseOrderReceiveResponse><ns1:purchaseOrderReceive><ns1:ordernum>PO026790</ns1:ordernum><ns1:lineDetail><ns1:ordernum>PO026790</ns1:ordernum><ns1:line>1</ns1:line></ns1:lineDetail></ns1:purchaseOrderReceive></ns1:dsPurchaseOrderReceiveResponse></ns1:receivePurchaseOrderResponse></soapenv:Body></soapenv:Envelope>
```

Perhatikan: `dsPurchaseOrderReceiveResponse` cuma echo balik `ordernum`+`line`, **tidak ada** info
qty yang ter-posting sama sekali — jadi dari sisi API kami memang tidak ada cara membedakan
"benar-benar ter-posting" vs "diterima tapi didiamkan".

---

## Detail teknis (konteks awal permintaan)

## Konteks

Aplikasi matex terhubung ke QAD via custom broker (prefix `SDI_`) di `qadeesdi.site:24079/qxi/services/QdocWebService` (environment **test**, domain `7000`) dan WSA di port yang sama untuk query.

Alur kami **tidak melalui Requisition (PR)** — beda dari project lain (warehouse) yang pakai `SDI_CreatePR` → approval → PO. Matex langsung:

1. `maintainPurchaseOrder` (operasi, dituju ke `SDI_BuatPO`) — bikin PO langsung, dapat nomor PO (mis. `PO026790`).
2. `receivePurchaseOrder` (operasi, dituju ke `SDI_eKanbanGR`) — catat penerimaan barang terhadap PO+line tersebut.

## Masalah

`receivePurchaseOrder` kadang membalas `<result>success</result>` bersih (tanpa warning/error), tapi qty received di PO **tidak benar-benar berubah** di QAD (silent no-op) — ini juga sudah pernah didokumentasikan tim yang membangun integrasi serupa di project lain kami.

Project lain itu mengatasinya dengan query ulang qty received setelah kirim, pakai fungsi WSA `SDI_getPRtoPO_` — **tapi fungsi itu dicari berdasarkan nomor Requisition (PR)**, bukan nomor PO. Karena alur matex tidak pernah bikin Requisition, kami tidak punya nomor PR untuk dipakai mencari lewat fungsi itu.

## Yang kami butuhkan

Fungsi (WSA atau QDoc, custom `SDI_` juga tidak masalah) untuk **query qty received / open qty per line, berdasarkan nomor PO langsung** (bukan nomor requisition) — semacam:

- Input: nomor PO (`ordernum`), nomor line
- Output: qty received (`pod_qty_rec` / sejenis) dan/atau qty open (`pod_qty_open`) untuk line tersebut

Ini dipakai untuk verifikasi otomatis: setiap kali kami memanggil `receivePurchaseOrder`, kami akan query qty sebelum & sesudah, dan baru menganggap berhasil kalau qty aktual di QAD benar-benar bertambah — sama seperti pola verifikasi yang sudah terbukti jalan di project lain kami, hanya saja dikunci ke nomor PO, bukan nomor requisition.

## Pertanyaan untuk admin QAD/Progress

1. **Paling utama:** kenapa `receivePurchaseOrder` (`SDI_eKanbanGR`) balas `success` tapi qty
   received tidak berubah untuk PO hasil `SDI_BuatPO`? Mohon cek source code Progress ABL kedua
   program ini, atau trace langsung transaksi untuk PO026787/PO026790/PO026793 di atas.
2. Apakah sudah ada program/broker untuk query qty received/open by nomor PO langsung (bukan
   requisition)? Kalau belum, bisa dibuatkan — mirroring `SDI_getPRtoPO_` tapi input-nya nomor
   PO (bukan `ipReqNbr`)?
3. **Preferensi kami untuk fallback:** kalau admin sudah punya akses **browse QXtend/WSA
   generik** ke tabel PO Detail (`pod_mstr`) — itu kemungkinan besar lebih cepat disiapkan
   daripada menulis broker custom baru dari nol. Cukup untuk baca `pod_qty_rec`/`pod_qty_open`
   per PO+line, tidak perlu logic tambahan apapun.
