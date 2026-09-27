import os
from PIL import Image

src = r"C:\Users\Lê Nhựt Khánh\.gemini\antigravity-ide\brain\65c4c695-5d33-499d-82da-55838622944a\.user_uploaded\media_1789810845493.jpg"
out_dir = r"c:\xampp\htdocs\tkb\assets\ai"
os.makedirs(out_dir, exist_ok=True)

img = Image.open(src)

# 1. Clean hero banner
hero = img.crop((178, 48, 1008, 208))
hero.save(os.path.join(out_dir, "vutru_hero_banner.jpg"), quality=98)

# 2. Cute 3D robot avatar (crop from hero, make it a nice square avatar)
# In original image, robot head and upper torso is at x=642 to 790, y=55 to 202
robot = img.crop((640, 52, 792, 204))
robot = robot.resize((256, 256), Image.Resampling.LANCZOS)
robot.save(os.path.join(out_dir, "vutru_robot_avatar.png"))

# 3. Spiral galaxy
galaxy = img.crop((842, 342, 988, 452))
galaxy.save(os.path.join(out_dir, "vutru_galaxy_spin.png"))

# 4. Planet pill icon
planet = img.crop((465, 282, 505, 314))
planet.save(os.path.join(out_dir, "vutru_saturn_pill.png"))

# 5. Course thumbnails
img.crop((195, 481, 267, 532)).save(os.path.join(out_dir, "course_prog.jpg"), quality=95)
img.crop((275, 481, 347, 532)).save(os.path.join(out_dir, "course_db.jpg"), quality=95)
img.crop((355, 481, 427, 532)).save(os.path.join(out_dir, "course_web.jpg"), quality=95)
img.crop((435, 481, 507, 532)).save(os.path.join(out_dir, "course_ai.jpg"), quality=95)

print("All precise assets generated successfully!")
