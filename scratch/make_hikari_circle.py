from PIL import Image, ImageDraw

src_path = 'c:/xampp/htdocs/tkb/assets/ai/vutru_hikari_5d_idle.png'
img = Image.open(src_path).convert('RGBA')

# Crop face area (Hikari face is near top center)
w, h = img.size
# Face is approx center x, y around 0.15 to 0.45
cx = w // 2
cy = int(h * 0.30)
r = int(min(w, h) * 0.18)

crop_box = (max(0, cx - r), max(0, cy - r), min(w, cx + r), min(h, cy + r))
face = img.crop(crop_box)

size = (180, 180)
face = face.resize(size, Image.Resampling.LANCZOS)

# Circular mask
mask = Image.new('L', size, 0)
draw = ImageDraw.Draw(mask)
draw.ellipse((0, 0, 180, 180), fill=255)

output = Image.new('RGBA', size, (0, 0, 0, 0))
output.paste(face, (0, 0), mask)

output.save('c:/xampp/htdocs/tkb/assets/ai/vutru_hikari_5d_circle.png', format='PNG')
output.save('c:/xampp/htdocs/tkb/assets/ai/vutru_hikari_5d_circle.webp', format='WEBP')
print("Successfully created vutru_hikari_5d_circle.png & .webp!")
