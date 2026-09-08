import ftplib
import os

FTP_HOSTS = ['ftpupload.net', 'ftp.infinityfree.com', 'ftp.epizy.com']
FTP_USER = 'if0_41796593'
FTP_PASS = 'T5v3vJeuvOxCI'

success = False
for host in FTP_HOSTS:
    try:
        print(f"Trying FTP host: {host}...")
        ftp = ftplib.FTP(host, timeout=15)
        ftp.login(FTP_USER, FTP_PASS)
        print(f"Connected to {host}!")
        
        # List dirs
        print("Root listing:")
        ftp.dir()
        
        # Navigate to htdocs / tkb / teacher
        # Check if htdocs exists
        lines = ftp.nlst()
        print("NLST:", lines)
        
        target_remote_dir = ""
        for possible in ['htdocs/tkb/teacher', 'htdocs/teacher', 'tkb/teacher']:
            try:
                ftp.cwd(possible)
                target_remote_dir = possible
                print(f"Found remote dir: {target_remote_dir}")
                break
            except Exception as e:
                pass
        
        if target_remote_dir:
            local_file = r'c:\xampp\htdocs\tkb\teacher\quanly_ai_accounts.php'
            with open(local_file, 'rb') as f:
                ftp.storbinary('STOR quanly_ai_accounts.php', f)
            print("Successfully uploaded quanly_ai_accounts.php!")
            
            # Also upload db_sync_data.sql if api dir exists
            try:
                ftp.cwd('../api')
                local_sql = r'c:\xampp\htdocs\tkb\api\db_sync_data.sql'
                if os.path.exists(local_sql):
                    with open(local_sql, 'rb') as f:
                        ftp.storbinary('STOR db_sync_data.sql', f)
                    print("Successfully uploaded db_sync_data.sql!")
            except Exception as e:
                print("Could not upload db_sync_data.sql:", e)
                
            success = True
            ftp.quit()
            break
        else:
            print("Could not find target remote directory.")
            ftp.quit()
    except Exception as e:
        print(f"Failed on {host}: {e}")

if success:
    print("ALL DONE SUCCESSFULLY!")
else:
    print("FTP UPLOAD FAILED")
