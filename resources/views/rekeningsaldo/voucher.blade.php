<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voucher Bank Keluar</title>

  <!-- Favicons -->
  <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
      --background-color: #f4f6f7;
      --text-color: #333;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Roboto', Arial, sans-serif;
      background-color: var(--background-color);
      line-height: 1.6;
      color: var(--text-color);
    }

    .container {
      max-width: 700px;
      margin: 30px auto;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
      overflow: hidden;
      border: 1px solid #e0e0e0;
    }

    .header {
      background-color: var(--primary-color);
      color: white;
      padding: 20px;
      position: relative;
    }

    .header-content {
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .header .logo {
      width: 100px;
      position: absolute;
      left: 20px;
    }

    .logo img {
      max-width: 100%;   /* Atur agar gambar logo tidak lebih lebar dari kontainer */
      height: auto;      /* Pertahankan rasio aspek gambar */
    }

    .header .title-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex-grow: 1;
      text-align: center;
    }

    .header .title {
      font-size: 26px;
      font-weight: 700;
      letter-spacing: 1px;
      margin-bottom: 5px;
    }

    .header .directorate {
      font-size: 16px;
      opacity: 0.8;
    }

    .voucher-details {
      padding: 20px;
      background-color: #f9f9f9;
    }

    .voucher-date-container {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
      border-bottom: 2px solid var(--secondary-color);
      padding-bottom: 10px;
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-bottom: 20px;
    }

    table td {
      padding: 12px;
      border-bottom: 1px solid #e0e0e0;
    }

    table:first-of-type td:first-child {
      font-weight: 600;
      width: 25%;
      background-color: #f1f3f4;
    }

    .signature-table {
      margin-top: 30px;
      text-align: center;
    }

    .signature-table td {
      padding: 15px;
      border-top: 1px solid #e0e0e0;
    }

    .signature-table .label {
      font-weight: 700;
      color: var(--primary-color);
    }

    .signature-lines {
      height: 100px;
      border-bottom: 1px dotted #ccc;
    }

    .footer {
      background-color: var(--primary-color);
      color: white;
      text-align: center;
      padding: 15px;
      font-size: 12px;
    }

    .btn-back {
      display: inline-block;
      padding: 10px 15px;
      font-size: 14px;
      background-color: #004cff;
      color: white;
      text-align: center;
      text-decoration: none;
      border-radius: 5px;
      margin-bottom: 10px;
    }

    .btn-print {
      display: inline-block;
      padding: 10px 15px;
      font-size: 14px;
      background-color: #004cff;
      color: white;
      text-align: center;
      text-decoration: none;
      border-radius: 5px;
      margin-bottom: 10px;
      cursor: pointer;
    }

    /* Media query for print */
    @media print {
      @page {
    size: 14.8cm 21cm; /* Ukuran A5 portrait: tinggi 14.8 cm, lebar 21 cm */
    margin: 10mm; /* Mengurangi margin agar lebih banyak ruang */
    }

    body {
      font-size: 12px; /* Mengurangi ukuran font untuk membuat teks lebih kecil */
      line-height: 1.2; /* Mengurangi jarak antar baris */
      margin: 0;
      padding: 0;
    }

    .container {
      padding: 12px; /* Mengurangi padding untuk kontainer */
      max-width: 100%; /* Sesuaikan lebar kontainer agar lebih efisien */
    }

    .header, .voucher-details, .signature-table {
      margin-bottom: 12px; /* Mengurangi margin bawah untuk section */
    }

    .header, .header-content, .title-section, .title, .directorate {
        color: black !important; /* Pastikan teks hitam */
    }

      .container {
        max-width: 100%; /* Sesuaikan lebar kontainer */
        margin: 0;
        box-shadow: none;
        border: none;
      }

      .btn-back {
        display: none;
      }

      .btn-print {
        display: none;
      }
    }

    @media screen {
      .container {
        max-width: 700px; /* Menyesuaikan lebar halaman */
      }
    }
  </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
      <div class="header-content">
        <div class="logo">
          <img src="{{ asset('logowps.png') }}" alt="Logo">
        </div>
        <div class="title-section">
          <div class="title">VOUCHER BANK KELUAR</div>
          <div class="directorate">DIREKTORAT:</div>
        </div>
      </div>
    </div>

    <!-- Voucher Details Section -->
    <div class="voucher-details">
      <div class="voucher-date-container">
        <div>No. Voucher: {{ $transaction->nomor_voucher }}</div>
        <div>Tanggal: {{ \Carbon\Carbon::parse($transaction->tanggal_transaksi)->format('d F Y') }}</div>
      </div>

      <table>
        <tr>
          <td>Pembayaran ke</td>
          <td>{{ $transaction->penerima }}</td>
        </tr>
        <tr>
          <td>Banyaknya Uang</td>
          @foreach ($transaction->rekeningsaldo_items as $index => $item)
            <td>Rp. {{ number_format($item->biaya, 2, ',', '.') }}</td>
          @endforeach
        </tr>
        <tr>
            <td>Terbilang</td>
            <td>{{ $transaction->terbilang }}</td>
        </tr> 

        @php
        // Mengumpulkan semua kategori dari items, menghindari duplikasi
        $kategoriUnik = collect();
        foreach($transaction->rekeningsaldo_items as $item) {
            if ($item->category) {
                $kategoriUnik->push($item->category->nama);
            }
        }
        // Menghilangkan duplikasi kategori
        $kategoriUnik = $kategoriUnik->unique()->join('  |  ');
        @endphp
    

        <tr>
          <td>Guna Pembayaran</td>
          <td>{{ $kategoriUnik }}</td>
        </tr>
      </table>

    <!-- Keterangan Section --> 
    <table> 
        <tr> 
            <td colspan="2" style="text-align: left; font-weight: bold; font-size: 12px;">*keterangan</td> 
        </tr>
        @foreach ($transaction->rekeningsaldo_items as $item)
        <tr>
            <td colspan="2" style="text-align: left; font-size: 12px;">
                <p class="keterangan" style="text-transform: lowercase;">
                    {!! Str::markdown($item->keterangan ?? '') !!}
                </p>
            </td>
        </tr>
    @endforeach    

      <!-- Signature Table -->
      <table class="signature-table">
        <tr class="label">
          <td>Pemohon</td>
          <td>Menyetujui</td>
          <td>Budget Kontrol</td>
          <td>Diterima Oleh</td>
        </tr>
        <tr>
          <td class="signature-lines"></td>
          <td class="signature-lines"></td>
          <td class="signature-lines"></td>
          <td class="signature-lines"></td>
          <td class="signature-lines"></td>
        </tr>
        <tr>
          <td>{{ $transaction->pemohon }}</td>
          <td>Finance</td>
          <td></td>
          <td></td>
        </tr>
      </table>
    </div>

    <!-- Action Buttons -->
    <div class="footer">
      <div class="action-buttons">
        <button class="btn-back" onclick="goBack()">
          ← Kembali
        </button>
        <button class="btn-print" onclick="printPage()">
          🖨️ Cetak Voucher Bank
        </button>
      </div>
    </div>

    <script>
      function printPage() {
        window.print();
      }

      function goBack() {
        window.history.back();
      }
    </script>
</body>
</html>
