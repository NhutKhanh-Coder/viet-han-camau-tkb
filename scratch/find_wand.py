import os, glob

for root, dirs, files in os.walk(r'c:\xampp\htdocs\tkb'):
    if '.git' in root: continue
    for f in files:
        if f.endswith(('.php', '.html', '.js', '.css')):
            p = os.path.join(root, f)
            try:
                with open(p, 'r', encoding='utf-8', errors='ignore') as fp:
                    lines = fp.readlines()
                    for idx, line in enumerate(lines):
                        if 'fa-wand' in line or 'wand-magic' in line or 'wand' in line:
                            if 'fa-wand' in line or 'wand-magic' in line:
                                print(f"{p}:{idx+1} -> {line.strip()[:100]}")
            except Exception as e:
                pass
