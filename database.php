<?php
require_once 'medoo.php';
// koneksikan ke database
// ini contoh menggunakan SQLite

 /*   $database = new medoo([
        'database_type' => 'sqlite',
        'database_file' => 'laporan.db',
    ]);
*/

// uncomment ini jika menggunakan mySQL atau mariaDB
// sesuaikan nama database, host, user, dan passwordnya
    $database = new medoo([
        'database_type' => 'mysql',
        'database_name' => 'laporan',
        'server' => 'localhost',
        'username' => 'root',
        'password' => 'rootroot',
        'charset' => 'utf8'
    ]);

function tambahanggota($iduser, $nama)
{
    global $database;
    $last_id = $database->insert('laporan', [
        'id'    => $iduser,
        'nama'  => $nama,
        'waktu' => date('d-m-Y H:i:s'). 'WIB',
    ]
);
    return $last_id;
}

// fungsi untuk Update laporan
function tambahlaporan($iduser, $pesan)
{
    global $database;
    $Update = $database->Update('laporan', [
        'id'    => $iduser,
        'waktu' =>  date('d-m-Y H:i:s').' WIB',
        'pesan' => $pesan,
    ],
[
        'id' => $iduser,
    ]
);
    return $Update;
}

// fungsi menghapus laporan
function hapuslaporan($iduser)
{
    global $database;
    $database->delete('laporan', [
        'AND' => [
            'id' => $iduser,
        ],
    ]);
    return '⛔️ laporan telah dihapus..';
}

// fungsi melihat daftar laporan 
function listlaporan($iduser, $nama, $page = 0)
{
    global $database;
    $hasil = '😢 Maaf ya, tidak ada catatan yang tersimpan..';
    $datas = $database->select('laporan', [
        'id',
        'nama',
        'waktu',
        'pesan',
    ]
    //[
     ///   'id' => $iduser,
    //]
);
    $jml = count($datas);
    if ($jml > 0) {
        $hasil = "✍🏽 *$jml laporan saya simpan Rapi :*\n";
        $n = 0;
        foreach ($datas as $data) {
            $n++;
            $hasil .= "\n$n. ".  " *$data[nama]* " .substr($data['pesan'], 0, 100)." \n⌛️ `$data[waktu]`";
            //$hasil .= "\n👀 /view\_$data[no]\n";
            $hasil .= "\n📛 Hapus laporan *$data[nama]*? /hapus\_$data[id]\n";
        }
    }
    return $hasil;
}

// fungsi melihat isi pesan laporan
/*function viewlaporan($iduser, $idpesan)
{
    global $database;
    $hasil = "😢 Maaf ya, laporanmu yang itu tidak ditemukan .\nMungkin saja bukan buatmu..";
    $datas = $database->select('laporan', [
        'no',
        'id',
        'waktu',
        'pesan',
    ], [
        'AND' => [
            'id' => $iduser,
            'no' => $idpesan,
        ],
    ]);
    $jml = count($datas);
    if ($jml > 0) {
        $data = $datas[0];
        $hasil = "✍🏽 Laporan nomor $data[no] yang tersimpan berisi:\n~~~~~~~~~~~~~~~~~~~~~~~\n";
        $hasil .= "\n$data[pesan]👍🏻\n\n⌛️ `$data[waktu]`";
        $hasil .= "\n\n📛 Hapus? /hapus\_$data[no]";
    }
    return $hasil;
} */

// fungsi mencari pesan di diary
function carilaporan($iduser, $pesan, $nama)
{
    global $database;
    $hasil = '😢 Maaf ya, apa yang kamu cari tidak ditemukan..'. "\n";
    $hasil .= 'Contoh penggunaan:'. "\nketik */cari nama tempat-nya*";
    $datas = $database->select('laporan', [
        'id',
        'nama',
        'waktu',
        'pesan',
    ], [
        'pesan[~]' => $pesan,  
    ]
    );
    $jml = count($datas);
    if ($jml > 0) {
        $hasil = "✍🏽 *$jml laporan yang kamu cari *\n";
        $n = 0;
        foreach ($datas as $data) {
            $n++;
            $hasil .= "\n$n.". " *$data[nama]* ".substr($data['pesan'], 0, 100)." \n⌛️ `$data[waktu]`";
            //$hasil .= "\n👀 /view\_$data[no]\n";
            $hasil .= "\n📛 Hapus laporan *$data[nama]*? ". "/hapus\_$data[id]" . "\n";
        }
    }
    return $hasil;
}