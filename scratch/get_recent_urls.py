import sqlite3, shutil, os, sys
sys.stdout.reconfigure(encoding='utf-8')

history_path = os.path.expanduser(r'~\AppData\Local\Google\Chrome\User Data\Default\History')
temp_path = os.path.join(os.getcwd(), 'scratch', 'temp_history2.db')

try:
    shutil.copy2(history_path, temp_path)
    conn = sqlite3.connect(temp_path)
    cur = conn.cursor()
    cur.execute("SELECT url, title, last_visit_time FROM urls ORDER BY last_visit_time DESC LIMIT 30")
    for r in cur.fetchall():
        print(f"Title: {r[1]}\nURL: {r[0]}\n")
    conn.close()
    if os.path.exists(temp_path):
        os.remove(temp_path)
except Exception as e:
    print("Error:", e)
