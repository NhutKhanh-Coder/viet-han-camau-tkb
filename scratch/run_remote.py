import ftplib
import urllib.request
import re
import subprocess

def run_remote_php(php_code, remote_name='temp_exec.php'):
    # 1. Write file locally
    local_path = f'c:/xampp/htdocs/tkb/scratch/{remote_name}'
    with open(local_path, 'w', encoding='utf-8') as f:
        f.write(php_code)
    
    # 2. Upload via FTP
    ftp = ftplib.FTP('ftpupload.net', timeout=15)
    ftp.login('if0_41796593', 'T5v3vJeuvOxCI')
    for loc in ['htdocs/tkb', 'viethan.free.nf/htdocs/tkb']:
        try:
            ftp.cwd('/')
            ftp.cwd(loc)
            with open(local_path, 'rb') as f:
                ftp.storbinary(f'STOR {remote_name}', f)
        except Exception as e:
            pass
    ftp.quit()
    
    # 3. Fetch output using PHP curl with cookie decryption
    fetcher = f"""<?php
    $ch = curl_init('https://viethan.free.nf/tkb/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $html = curl_exec($ch);
    curl_close($ch);

    $cookieVal = null;
    if (preg_match('/toNumbers\("([a-f0-9]+)"\),b=toNumbers\("([a-f0-9]+)"\),c=toNumbers\("([a-f0-9]+)"\)/i', $html, $m)) {{
        $key = hex2bin($m[1]);
        $iv  = hex2bin($m[2]);
        $ct  = hex2bin($m[3]);
        $dec = openssl_decrypt($ct, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        $cookieVal = bin2hex($dec);
    }}

    $ch2 = curl_init('https://viethan.free.nf/tkb/{remote_name}');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0');
    if ($cookieVal) curl_setopt($ch2, CURLOPT_COOKIE, "__test=" . $cookieVal);
    echo curl_exec($ch2);
    curl_close($ch2);
    """
    with open(r'c:\xampp\htdocs\tkb\scratch\_fetch_temp.php', 'w', encoding='utf-8') as f:
        f.write(fetcher)
        
    res = subprocess.run([r'c:\xampp\php\php.exe', r'c:\xampp\htdocs\tkb\scratch\_fetch_temp.php'], capture_output=True, encoding='utf-8', errors='replace')
    return res.stdout

if __name__ == '__main__':
    code = """<?php
    require_once __DIR__ . '/config.php';
    $db = getDB();
    echo "=== GIANG_VIEN ===\\n";
    $r = $db->query("SELECT id, ma_gv, ho_ten, khoa FROM giang_vien");
    while ($row = $r->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\\n";
    """
    print(run_remote_php(code, 'inspect_gv.php'))
