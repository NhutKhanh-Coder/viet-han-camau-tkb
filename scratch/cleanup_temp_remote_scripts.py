import ftplib

ftp = ftplib.FTP('ftpupload.net', timeout=15)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

temp_files = ['exec_delete_tailieu.php', 'check_remote_tailieu.php', 'check_remote_lessons.php', 'temp_exec.php']

for rdir in ['viethan.free.nf/htdocs/tkb', 'htdocs/tkb']:
    try:
        ftp.cwd('/')
        ftp.cwd(rdir)
        files = ftp.nlst()
        for f in temp_files:
            if f in files:
                ftp.delete(f)
                print(f"Deleted temp file {f} from {rdir}")
    except Exception as e:
        print(f"Error {rdir}: {e}")

ftp.quit()
print("Cleaned up temp remote execution files.")
