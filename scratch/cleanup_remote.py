import ftplib

ftp = ftplib.FTP('ftpupload.net', timeout=15)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

for loc in ['htdocs/tkb', 'viethan.free.nf/htdocs/tkb', 'htdocs', 'viethan.free.nf/htdocs']:
    try:
        ftp.cwd('/')
        ftp.cwd(loc)
        ftp.delete('clean_remote.php')
        print(f"Deleted clean_remote.php from {loc}")
    except Exception as e:
        pass

ftp.quit()
print("Cleaned up!")
