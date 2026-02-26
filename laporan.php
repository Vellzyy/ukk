<?php 
include 'config.php'; 

// Solusi Error Notice: Cek agar tidak double session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek keamanan: Hanya Admin atau Petugas yang bisa cetak
if (!isset($_SESSION['role']) || $_SESSION['role'] == 'peminjam') { 
    header("location: index.php"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Peminjaman Olahraga</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; padding: 30px; color: #000; background: #fff; }
        .header { text-align: center; border-bottom: 3px double #000; margin-bottom: 20px; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 22px; }
        .header p { margin: 5px 0; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px 10px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; text-transform: uppercase; text-align: center; font-weight: bold; }
        
        .text-center { text-align: center; }
        .total-row { background-color: #eee; font-weight: bold; }
        
        .footer { margin-top: 50px; float: right; text-align: center; width: 250px; font-size: 14px; }
        .signature { margin-top: 60px; font-weight: bold; text-decoration: underline; }
        
        /* Navigasi saat di layar browser */
        .no-print { 
            margin-bottom: 20px; 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            border: 1px solid #ddd;
        }

        /* Pengaturan saat dicetak */
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            @page { size: landscape; margin: 1cm; } 
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #198754; color: white; border: none; border-radius: 5px; font-weight: bold;">🖨️ CETAK LAPORAN</button>
            <a href="dashboard.php" style="margin-left: 15px; text-decoration: none; color: #7C0A02; font-weight: bold;">← Kembali ke Dashboard</a>
        </div>
        <div style="font-size: 13px; color: #666;">
            Mode: <b>Landscape</b> | Printer Ready
        </div>
    </div>

    <div class="header">
        <h2>Laporan Aktivitas Peminjaman Alat Olahraga</h2>
        <p>Unit Inventaris Sarana & Prasarana Olahraga</p>
        <p style="font-style: italic; font-size: 12px;">Dicetak pada: <?= date('d/m/Y H:i:s'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Peminjam</th>
                <th>Nama Barang</th>
                <th width="120">Tgl Pinjam</th>
                <th width="120">Tgl Kembali</th>
                <th width="80">Status</th>
                <th>Kondisi / Alasan</th>
                <th width="100">Denda</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_denda = 0;
            $count_transaksi = 0;

            $sql = "SELECT users.username, alat.nama_alat, peminjaman.tgl_minta, 
                           peminjaman.tgl_kembali, peminjaman.status_pjm, 
                           peminjaman.kondisi_akhir, peminjaman.alasan_tolak, peminjaman.denda 
                    FROM peminjaman 
                    JOIN users ON peminjaman.id_user = users.id_user 
                    JOIN alat ON peminjaman.id_alat = alat.id_alat 
                    WHERE peminjaman.status_pjm IN ('selesai', 'ditolak')
                    ORDER BY peminjaman.id_peminjaman DESC";
            
            $res = mysqli_query($conn, $sql);
            while($l = mysqli_fetch_assoc($res)) : 
                $total_denda += $l['denda'];
                $count_transaksi++;
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><b><?= strtoupper($l['username']); ?></b></td>
                <td><?= $l['nama_alat']; ?></td>
                <td class="text-center"><?= date('d/m/Y H:i', strtotime($l['tgl_minta'])); ?></td>
                <td class="text-center">
                    <?= ($l['tgl_kembali'] != '0000-00-00 00:00:00' && $l['tgl_kembali'] != null) 
                        ? date('d/m/Y H:i', strtotime($l['tgl_kembali'])) 
                        : '-'; ?>
                </td>
                <td class="text-center">
                    <span style="font-weight: bold; color: <?= ($l['status_pjm'] == 'selesai') ? 'green' : 'red'; ?>">
                        <?= strtoupper($l['status_pjm']); ?>
                    </span>
                </td>
                <td>
                    <?php 
                    if($l['status_pjm'] == 'ditolak') {
                        echo "ALASAN: " . ($l['alasan_tolak'] ?: 'Alat tidak tersedia');
                    } else {
                        echo $l['kondisi_akhir'] ?: 'Baik';
                    }
                    ?>
                </td>
                <td style="text-align: right;">Rp <?= number_format($l['denda'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if ($count_transaksi == 0) : ?>
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px;">Belum ada data riwayat peminjaman.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" style="text-align: right; padding-right: 15px;">TOTAL PENERIMAAN DENDA :</td>
                <td style="text-align: right;">Rp <?= number_format($total_denda, 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 15px; font-size: 12px;">
        Total Data Riwayat: <b><?= $count_transaksi; ?> Transaksi</b>
    </div>

    <div class="footer">
        <p>Medan, <?= date('d F Y'); ?></p>
        <p>Petugas Inventaris,</p>
        <div class="signature"><?= strtoupper($_SESSION['username']); ?></div>
        <p style="font-size: 11px; margin-top: 5px;">ID Petugas: #<?= $_SESSION['id_user']; ?></p>
    </div>

</body>
</html>