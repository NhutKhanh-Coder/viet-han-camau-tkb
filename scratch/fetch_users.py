import urllib.request
import re
import ssl
import subprocess

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

req = urllib.request.Request('https://viethan.free.nf/tkb/_dump_users.php', headers={'User-Agent': 'Mozilla/5.0'})
try:
    resp = urllib.request.urlopen(req, context=ctx).read().decode('utf-8', 'ignore')
    m = re.search(r'toNumbers\("([a-f0-9]+)"\),b=toNumbers\("([a-f0-9]+)"\),c=toNumbers\("([a-f0-9]+)"\)', resp)
    if m:
        cmd = f'c:\\xampp\\php\\php.exe -r "echo bin2hex(openssl_decrypt(hex2bin(\'{m.group(3)}\'), \'AES-128-CBC\', hex2bin(\'{m.group(1)}\'), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, hex2bin(\'{m.group(2)}\')));"'
        cookie = subprocess.check_output(cmd, shell=True).decode().strip()
        req2 = urllib.request.Request('https://viethan.free.nf/tkb/_dump_users.php', headers={'User-Agent': 'Mozilla/5.0', 'Cookie': f'__test={cookie}'})
        print('Trigger resp:', urllib.request.urlopen(req2, context=ctx).read().decode('utf-8', 'ignore')[:100])
        
        req3 = urllib.request.Request('https://viethan.free.nf/tkb/_users_dump.json', headers={'User-Agent': 'Mozilla/5.0', 'Cookie': f'__test={cookie}'})
        print('Users dump:', urllib.request.urlopen(req3, context=ctx).read().decode('utf-8', 'ignore')[:500])
except Exception as e:
    print('Error:', e)
