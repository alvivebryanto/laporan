<?php
/**
 * laporan pekerjaan
 * Bot PHP untuk membuat laporan pekerjaan sederhana
 * Versi 0.1
 * 22 Maret 2019
 * Last Update : 22 Maret 2019
 *
 * Default adalah poll!
 */
/* buatlah file token.php isinya :
<?php
$token = "isiTokenBotmu";
*/

require_once 'token.php';
// masukkan bot token di sini
define('BOT_TOKEN', $token);
// versi official telegram bot
 define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
// versi 3rd party, biar bisa tanpa https / tanpa SSL.
//define('API_URL', 'https://api.pwrtelegram.xyz/bot'.BOT_TOKEN.'/');
//define('myVERSI', '0.1');
//define('lastUPDATE', '21 maret 2019');
// ambil databasenya
require_once 'database.php';
// aktifkan ini jika ingin menampilkan debugging poll
$debug = false;
function exec_curl_request($handle)
{
    $response = curl_exec($handle);
    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error\n");
        curl_close($handle);
        return false;
    }
    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);
    if ($http_code >= 500) {
        // do not wat to DDOS server if something goes wrong
    sleep(10);
        return false;
    } elseif ($http_code != 200) {
        $response = json_decode($response, true);
        error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
        if ($http_code == 401) {
            throw new Exception('Invalid access token provided');
        }
        return false;
    } else {
        $response = json_decode($response, true);
        if (isset($response['description'])) {
            error_log("Request was successfull: {$response['description']}\n");
        }
        $response = $response['result'];
    }
    return $response;
}
function apiRequest($method, $parameters = null)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }
    if (!$parameters) {
        $parameters = [];
    } elseif (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }
    foreach ($parameters as $key => &$val) {
        // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
        $val = json_encode($val);
    }
    }
    $url = API_URL.$method.'?'.http_build_query($parameters);
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    return exec_curl_request($handle);
}
function apiRequestJson($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }
    if (!$parameters) {
        $parameters = [];
    } elseif (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }
    $parameters['method'] = $method;
    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    return exec_curl_request($handle);
}
// jebakan token, klo ga diisi akan mati
if (strlen(BOT_TOKEN) < 20) {
    die(PHP_EOL."-> -> Token BOT API nya mohon diisi dengan benar!\n");
}
function getUpdates($last_id = null)
{
    $params = [];
    if (!empty($last_id)) {
        $params = ['offset' => $last_id + 1, 'limit' => 1];
    }
  //echo print_r($params, true);
  return apiRequest('getUpdates', $params);
}
// matikan ini jika ingin bot berjalan
//die('baca dengan teliti yak!');
// ----------- pantengin mulai ini
function sendMessage($idpesan, $idchat, $pesan, $nama)
{
    $data = [
    'chat_id'             => $idchat,
    'nama'                => $nama,
    'text'                => $pesan,
    'parse_mode'          => 'Markdown',
    'reply_to_message_id' => $idpesan,
  ];
    return apiRequest('sendMessage', $data);
}
function processMessage($message)
{
    global $database;
    if ($GLOBALS['debug']) {
        print_r($message);
    }
    if (isset($message['message'])) {
        $sumber = $message['message'];
        $idpesan = $sumber['message_id'];
        $idchat = $sumber['chat']['id'];
        $nama = $sumber['from']['nama'];
        $iduser = $sumber['from']['id'];
        if (isset($sumber['text'])) {
            $pesan = $sumber['text'];
            if (preg_match("/^\/view_(\d+)$/i", $pesan, $cocok)) {
                $pesan = "/view $cocok[1]";
            }
            if (preg_match("/^\/hapus_(\d+)$/i", $pesan, $cocok)) {
                $pesan = "/hapus $cocok[1]";
            }
     // print_r($pesan);
      $pecah = explode(' ', $pesan, 2);
            $katapertama = strtolower($pecah[0]);
            switch ($katapertama) {
        case '/start':
          $text = "Hai `$nama`.. selamat datang di laporan harian!\n\nUntuk membantuan anda dalam melaporkn kegiatan ketik: */bantuan*";
          break;
        case '/bantuan':
          $text = '💁🏼 Saya adalah *bot laporan harian*'."\n";
          date_default_timezone_set('Asia/Jakarta');
          $text .= "⏰ Jam : ". date('H:i:s'). "\n";
           $text .= "📋 Tanggal : ". date('d-m-Y') . "\n\n";
          $text .= "💌 Berikut menu yang tersedia untuk mempermudah kamu melaporkan kegiatan harian : \n\n";
          $text .= '🤖 untuk yang belum berkenalan dengan *bot laporan pekerjaan* silahkan ketik */daftar lalu tulis namamu*'. "\n\n";
          $text .= '➕ untuk menambah laporan harian pekerjaan ketik */lapor* lalu tulis laporan hari ini'. "\n\n";
          $text .= '🔃 ketik */lihat* untuk melihat daftar laporan harian yang tersedia' . "\n\n";
          $text .= '🔍 untuk mencari laporan harian pekerjaan ketik */cari* lalu tulis kata yang dicari' . "\n\n";
          //$text .= "⌛️ /time info waktu sekarang\n\n";
          //$text .= "🆘 /help info bantuan ini\n\n";
          $text .= '😎 *Terimakasi dan selamat bekerja*';
          break;

/*      case '/time':
          $text = "⌛️ Waktu Sekarang :\n";
          date_default_timezone_set('Asia/Jakarta');
          $text .= date('d-m-Y H:i:s') ;
        break;
*/  

         //untuk menambah anggota 
        case '/daftar':
          if (isset($pecah[1])) {
              $pesanproses = $pecah[1];
              $r = tambahanggota($iduser, $pesanproses,$nama);
              $text = '😘 kamu berhasil mendaftar!';
              
          }
          else {
              $text = '⛔️ *ERROR:* _kamu belum terdaftar_';
              $text .= "\n\nContoh: `/daftar Namamu`";
          }
          break;

          //untuk menambah laporan pekerjaan 
        case '/lapor':
          if (isset($pecah[1])) {
              $pesanproses = $pecah[1];
              $r = tambahlaporan($iduser, $pesanproses);
              $text = '😘 Laporan pekerjaanmu telah berhasil di simpan!';
             // $text = '😘 kamu harus mendaftar terlebih dahulu sebelum melaporkan kegiatan!';
          } else {
              $text = '⛔️ *ERROR:* _kamu belum mendaftar atau pesan yang ditambahkan tidak boleh kosong!_';
              $text .= "\n\nContoh: `/lapor pekanbaru aman bro!`";
          }
          break;

          //untuk melihat laporan pekerjaan berdasarkan kata yang dipilih
/*        case '/view':
          if (isset($pecah[1])) {
              $pesanproses = $pecah[1];
              $text = viewlaporan($iduser, $pesanproses);
          } else {
              $text = '⛔️ *ERROR:* `nomor pesan tidak boleh kosong.`';
          }
          break;
*/
          //untuk menghapus laporan pekerjaan
        case '/hapus':
          if (isset($pecah[1])) {
              $pesanproses = $pecah[1];
              $text = hapuslaporan($iduser, $pesanproses);
          } else {
              $text = '⛔️ *ERROR:* `nomor pesan tidak boleh kosong.`';
          }
          break;

          // untuk melihat semua laporan pekerjaan
        case '/lihat':
          $text = listlaporan($iduser, $nama);
          if ($GLOBALS['debug']) {
              print_r($text);
          }
          break;

          //untuk mencari kata pada laporan pekerjaan 
        case '/cari':
          // saya gunakan pregmatch ini salah satunya untuk mencegah SQL injection
          // hanya huruf dan angka saja yang akan diproses
          if (preg_match("/^\/cari ((\w| )+)$/i", $pesan, $cocok)) {
              $pesanproses = $cocok[1];
              $text = carilaporan($iduser, $pesanproses, $nama);
          } else {
              $text = '⛔️ *ERROR:* Contoh penggunaan:'. "\nketik */cari nama tempat-nya*";
          }
          break;
        default:
          $text = '😥 _saya tidak mengerti apa maksudmu_';
          break;
      }
        } else {
            $text = 'Haloo..';
        }
        $hasil = sendMessage($idpesan, $idchat, $text, $nama);
        if ($GLOBALS['debug']) {
            // hanya nampak saat metode poll dan debug = true;
      echo 'Pesan yang dikirim: '.$text.PHP_EOL;
            print_r($hasil);
        }
    }
}

// pencetakan versi dan info waktu server, berfungsi jika test hook
date_default_timezone_set('Asia/Jakarta');
echo 'Indonesian Timezone: ' . date('d-m-Y H:i:s');
function printUpdates($result)
{
    foreach ($result as $obj) {
        // echo $obj['message']['text'].PHP_EOL;
    processMessage($obj);
        $last_id = $obj['update_id'];
    }
    return $last_id;
}

// AKTIFKAN INI jika menggunakan metode poll
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/*$last_id = null;
while (true) {
    $result = getUpdates($last_id);
    if (!empty($result)) {
        echo '+';
        $last_id = printUpdates($result);
    } else {
        echo '-';
    }
    sleep(1);
}
*/

// AKTIFKAN INI jika menggunakan metode webhook
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) {
  // ini jebakan jika ada yang iseng mengirim sesuatu ke hook
  // dan tidak sesuai format JSON harus ditolak!
  exit;
} else {
  // sesuai format JSON, proses pesannya
  processMessage($update);
}
/*
Sekian.
*/