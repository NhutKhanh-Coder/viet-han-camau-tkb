import urllib.request
import re
import ssl
import subprocess

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

url = 'https://viethan.free.nf/tkb/admin/quanly_ai_models.php'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
try:
    resp = urllib.request.urlopen(req, context=ctx).read().decode('utf-8', 'ignore')
    m = re.search(r'toNumbers\("([a-f0-9]+)"\),b=toNumbers\("([a-f0-9]+)"\),c=toNumbers\("([a-f0-9]+)"\)', resp)
    if m:
        c1, c2, c3 = m.group(1), m.group(2), m.group(3)
        php_cmd = f"echo bin2hex(openssl_decrypt(hex2bin('{c3}'), 'AES-128-CBC', hex2bin('{c1}'), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, hex2bin('{c2}')));"
        cookie = subprocess.check_output(['c:\\xampp\\php\\php.exe', '-r', php_cmd]).decode().strip()
        req2 = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0', 'Cookie': f'__test={cookie}'})
        resp2 = urllib.request.urlopen(req2, context=ctx)
        print('HTTP Code:', resp2.getcode())
        print('Final URL:', resp2.geturl())
        content = resp2.read().decode('utf-8', 'ignore')
        print('Content length:', len(content))
        print('Snippet:', content[:400])
except Exception as e:
    print('Error:', e)
