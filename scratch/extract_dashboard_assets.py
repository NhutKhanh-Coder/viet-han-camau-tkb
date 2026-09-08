from PIL import Image
import os

source_path = r"C:\Users\Lê Nhựt Khánh\.gemini\antigravity-ide\brain\37b05d48-c3f6-4958-8e6a-b8769fd4a83b\.user_uploaded\media_1788333578746.jpg"
output_dir = r"c:\xampp\htdocs\tkb\assets\img"
os.makedirs(output_dir, exist_ok=True)

img = Image.open(source_path)
w, h = img.size
print(f"Image dimensions: {w} x {h}")

# Normalize coordinates based on w x h
# In 1024 x 576 or original size:
# The right panel is around x: 0.74 to 0.98 of width
# 1. Avatar of Phuong Anh (in the circular frame)
# 2. 4 Gallery photos
# 3. Anime video player

# Let's inspect coordinates precisely:
# Let's crop Phuong Anh main avatar:
# x: ~760 to ~860, y: ~160 to ~340 in 1024x576 proportionally
crop_avatar = (int(w * 0.755), int(h * 0.165), int(w * 0.860), int(h * 0.340))
avatar_img = img.crop(crop_avatar)
avatar_img.save(os.path.join(output_dir, "phuong_anh_avatar.png"))
print("Saved phuong_anh_avatar.png")

# Gallery photos (4 items in a row around y: ~420 to ~530 / height ratio 0.42 to 0.525)
crop_g1 = (int(w * 0.755), int(h * 0.425), int(w * 0.810), int(h * 0.528))
img.crop(crop_g1).save(os.path.join(output_dir, "phuong_anh_1.png"))

crop_g2 = (int(w * 0.813), int(h * 0.425), int(w * 0.868), int(h * 0.528))
img.crop(crop_g2).save(os.path.join(output_dir, "phuong_anh_2.png"))

crop_g3 = (int(w * 0.871), int(h * 0.425), int(w * 0.925), int(h * 0.528))
img.crop(crop_g3).save(os.path.join(output_dir, "phuong_anh_3.png"))

crop_g4 = (int(w * 0.928), int(h * 0.425), int(w * 0.982), int(h * 0.528))
img.crop(crop_g4).save(os.path.join(output_dir, "phuong_anh_4.png"))
print("Saved phuong_anh 1-4.png")

# Anime video player background (around y: ~0.60 to ~0.80)
crop_video = (int(w * 0.755), int(h * 0.605), int(w * 0.985), int(h * 0.810))
img.crop(crop_video).save(os.path.join(output_dir, "anime_video_thumb.png"))
print("Saved anime_video_thumb.png")

# Sakura blossom at bottom-left corner
crop_sakura = (int(w * 0.0), int(h * 0.85), int(w * 0.08), int(h * 1.0))
img.crop(crop_sakura).save(os.path.join(output_dir, "sakura_corner.png"))
print("Saved sakura_corner.png")
