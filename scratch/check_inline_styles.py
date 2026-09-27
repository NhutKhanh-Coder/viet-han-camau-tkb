# -*- coding: utf-8 -*-
import re

with open('admin/ai_studio.php', 'r', encoding='utf-8') as f:
    text = f.read()

body_text = text[text.find('</head>'):]

matches = re.findall(r'style="[^"]*(?:background|color|border)[^"]*"', body_text)
print(f'Inline styles found: {len(matches)}')
for m in set(matches[:30]):
    print(m)
