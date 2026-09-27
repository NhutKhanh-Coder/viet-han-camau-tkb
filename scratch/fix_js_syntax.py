with open('c:/xampp/htdocs/tkb/admin/ai_studio.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Make sure persona uses gameCurrentChar
content = content.replace(
    "body: JSON.stringify({ message: val, model: studioModel, persona: 'anime' })",
    "body: JSON.stringify({ message: val, model: studioModel, persona: (gameCurrentChar === 'ani' ? 'ani' : 'anime') })"
)

content = content.replace(
    'setGameDialogue("Hikari luôn ở đây bên Keria! Hãy thử lại câu hỏi nhé! (◕‿◕)✨", true);',
    'setGameDialogue(gameCurrentChar === "ani" ? "Ani luôn ở đây bên Keria! Hãy thử lại câu hỏi nhé! (◕‿-)🖤" : "Hikari luôn ở đây bên Keria! Hãy thử lại câu hỏi nhé! (◕‿◕)✨", true);'
)

with open('c:/xampp/htdocs/tkb/admin/ai_studio.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated persona and catch text in ai_studio.php!")
