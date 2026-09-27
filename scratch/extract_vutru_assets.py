import os
from PIL import Image

src = r"C:\Users\Lê Nhựt Khánh\.gemini\antigravity-ide\brain\65c4c695-5d33-499d-82da-55838622944a\.user_uploaded\media_1789810845493.jpg"
out_dir = r"c:\xampp\htdocs\tkb\assets\ai"
os.makedirs(out_dir, exist_ok=True)

img = Image.open(src)
w, h = img.size
print(f"Loaded image size: {w}x{h}")

# Save complete mockup
img.save(os.path.join(out_dir, "vutru_full_mockup.jpg"), quality=95)

# 1. Hero banner: roughly from x=176 to 1010, y=48 to 216
hero_box = (176, 48, 1010, 216)
hero_img = img.crop(hero_box)
hero_img.save(os.path.join(out_dir, "vutru_hero_banner.jpg"), quality=95)
print("Saved vutru_hero_banner.jpg")

# 2. Robot Avatar (from top-left sidebar)
# Let's crop from sidebar top: x=7 to 60, y=7 to 55
avatar_box = (7, 6, 61, 56)
avatar_img = img.crop(avatar_box)
avatar_img.save(os.path.join(out_dir, "vutru_robot_avatar.png"))
print("Saved vutru_robot_avatar.png")

# 2b. Robot from chat header (x=542, y=284, w=35, h=35)
chat_robot_box = (542, 284, 579, 321)
chat_robot_img = img.crop(chat_robot_box)
chat_robot_img.save(os.path.join(out_dir, "vutru_chat_robot.png"))
print("Saved vutru_chat_robot.png")

# 3. Spiral Galaxy in chat message (around x=840 to 990, y=340 to 455)
galaxy_box = (840, 340, 990, 455)
galaxy_img = img.crop(galaxy_box)
galaxy_img.save(os.path.join(out_dir, "vutru_galaxy_spin.png"))
print("Saved vutru_galaxy_spin.png")

# 4. Floating Castle in bottom-left sidebar (x=8 to 168, y=455 to 555)
castle_box = (8, 455, 168, 555)
castle_img = img.crop(castle_box)
castle_img.save(os.path.join(out_dir, "vutru_castle_island.png"))
print("Saved vutru_castle_island.png")

# 5. Course Thumbnails in "Bệ phóng tri thức"
# Around y=475 to 535, x=190 to 516
c1_box = (194, 480, 268, 532)
c2_box = (274, 480, 348, 532)
c3_box = (354, 480, 428, 532)
c4_box = (434, 480, 508, 532)

img.crop(c1_box).save(os.path.join(out_dir, "course_prog.jpg"), quality=95)
img.crop(c2_box).save(os.path.join(out_dir, "course_db.jpg"), quality=95)
img.crop(c3_box).save(os.path.join(out_dir, "course_web.jpg"), quality=95)
img.crop(c4_box).save(os.path.join(out_dir, "course_ai.jpg"), quality=95)
print("Saved course thumbnails")

# 6. Planet icon in "Khám phá thế giới AI"
planet_box = (460, 280, 506, 318)
img.crop(planet_box).save(os.path.join(out_dir, "vutru_saturn_pill.png"))
print("Saved vutru_saturn_pill.png")

print("ALL CROPS COMPLETED SUCCESSFULLY!")
