from PIL import Image

img = Image.open(r"C:\Users\Lê Nhựt Khánh\.gemini\antigravity-ide\brain\65c4c695-5d33-499d-82da-55838622944a\.user_uploaded\media_1789810845493.jpg")

# Crop robot from hero:
robot_hero = img.crop((610, 20, 800, 210))
robot_hero.save(r"c:\xampp\htdocs\tkb\assets\ai\vutru_robot_hero.png")

# Also crop clean hero banner (remove bottom sliver)
clean_hero = img.crop((176, 48, 1010, 208))
clean_hero.save(r"c:\xampp\htdocs\tkb\assets\ai\vutru_hero_banner.jpg", quality=95)

print("Updated crops!")
