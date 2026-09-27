import ftplib

ftp = ftplib.FTP('ftpupload.net', timeout=15)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

for loc in ['htdocs/tkb/assets/uploads/documents', 'viethan.free.nf/htdocs/tkb/assets/uploads/documents']:
    try:
        ftp.cwd('/')
        ftp.cwd(loc)
        print(f"=== Files in {loc} ===")
        print(ftp.nlst())
    except Exception as e:
        print(f"Error {loc}: {e}")

ftp.quit()
