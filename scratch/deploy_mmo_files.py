import ftplib
import os

ftp = ftplib.FTP('ftpupload.net', timeout=20)
ftp.login('if0_41796593', 'T5v3vJeuvOxCI')

files_to_upload = [
    ('teacher/quanly_ai_accounts.php', 'teacher/quanly_ai_accounts.php'),
    ('student/shop_ai.php', 'student/shop_ai.php')
]

for base_dir in ['htdocs/tkb', 'viethan.free.nf/htdocs/tkb', 'htdocs', 'viethan.free.nf/htdocs']:
    try:
        ftp.cwd('/')
        ftp.cwd(base_dir)
        print(f"Uploading to {base_dir}...")
        for local_rel, remote_rel in files_to_upload:
            local_path = os.path.join('c:\\xampp\\htdocs\\tkb', local_rel)
            if os.path.exists(local_path):
                # Ensure remote subfolder exists
                subfolder = os.path.dirname(remote_rel)
                if subfolder:
                    try:
                        ftp.mkd(subfolder)
                    except:
                        pass
                with open(local_path, 'rb') as f:
                    ftp.storbinary(f'STOR {remote_rel}', f)
                print(f"  Uploaded {local_rel} -> {remote_rel}")
    except Exception as e:
        print(f"Error for {base_dir}: {e}")

ftp.quit()
print("FTP upload completed successfully!")
