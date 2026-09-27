import ftplib
import os

ftp = ftplib.FTP('ftpupload.net', timeout=20)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

files_to_sync = [
    {
        'local': r'c:\xampp\htdocs\tkb\student\hoc_bai.php',
        'remote_dirs': ['htdocs/tkb/student', 'viethan.free.nf/htdocs/tkb/student'],
        'filename': 'hoc_bai.php'
    },
    {
        'local': r'c:\xampp\htdocs\tkb\student\tailieu.php',
        'remote_dirs': ['htdocs/tkb/student', 'viethan.free.nf/htdocs/tkb/student'],
        'filename': 'tailieu.php'
    },
    {
        'local': r'c:\xampp\htdocs\tkb\includes\student_nav.php',
        'remote_dirs': ['htdocs/tkb/includes', 'viethan.free.nf/htdocs/tkb/includes'],
        'filename': 'student_nav.php'
    }
]

for item in files_to_sync:
    local_path = item['local']
    filename = item['filename']
    for rdir in item['remote_dirs']:
        try:
            ftp.cwd('/')
            ftp.cwd(rdir)
            with open(local_path, 'rb') as f:
                ftp.storbinary(f'STOR {filename}', f)
            print(f"Uploaded {filename} -> {rdir}")
        except Exception as e:
            print(f"Error {filename} -> {rdir}: {e}")

ftp.quit()
print("All files synced successfully!")
