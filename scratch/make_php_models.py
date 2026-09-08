import json

with open('scratch/all_models.json', 'r', encoding='utf-8') as f:
    models = json.load(f)

lines = ['<?php', '// Danh sach 36 Models AI he thong ho tro boi xKiro', '$SYSTEM_AI_MODELS = [']
for m in models:
    lines.append('    [')
    lines.append(f"        'id' => {json.dumps(m.get('id', ''))},")
    lines.append(f"        'name' => {json.dumps(m.get('name', ''))},")
    lines.append(f"        'provider' => {json.dumps(m.get('provider', ''))},")
    lines.append(f"        'badge' => {json.dumps(m.get('badge', ''))},")
    lines.append(f"        'context' => {json.dumps(m.get('context', ''))},")
    lines.append(f"        'desc' => {json.dumps(m.get('desc', ''))},")
    lines.append(f"        'isPaid' => {'true' if m.get('isPaid') else 'false'}")
    lines.append('    ],')
lines.append('];')
lines.append('')

with open('includes/ai_models_list.php', 'w', encoding='utf-8') as f:
    f.write('\n'.join(lines))

print('Wrote includes/ai_models_list.php successfully')
