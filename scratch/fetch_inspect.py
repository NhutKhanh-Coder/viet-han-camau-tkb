import requests
import re
import binascii
from Crypto.Cipher import AES

session = requests.Session()
session.headers.update({'User-Agent': 'Mozilla/5.0'})
r = session.get('https://viethan.free.nf/tkb/login.php')

m = re.search(r'toNumbers\("([a-f0-9]+)"\),b=toNumbers\("([a-f0-9]+)"\),c=toNumbers\("([a-f0-9]+)"\)', r.text)
if m:
    key = bytes.fromhex(m.group(1))
    iv = bytes.fromhex(m.group(2))
    ct = bytes.fromhex(m.group(3))
    cipher = AES.new(key, AES.MODE_CBC, iv)
    pt = cipher.decrypt(ct)
    cookie_val = pt.hex()
    session.cookies.set('__test', cookie_val)

r2 = session.get('https://viethan.free.nf/tkb/remote_inspect_all.php')
print(r2.text)
