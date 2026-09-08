import os
from PIL import Image

src_path = r"c:\xampp\htdocs\tkb\assets\img\admin_hero_laptop.png"
dst_path = r"c:\xampp\htdocs\tkb\assets\img\admin_hero_laptop.png"

img = Image.open(src_path).convert("RGBA")
width, height = img.size
pixdata = img.load()

for y in range(height):
    for x in range(width):
        r, g, b, a = pixdata[x, y]
        
        # Check saturation & brightness
        max_c = max(r, g, b)
        min_c = min(r, g, b)
        diff = max_c - min_c
        brightness = (r * 299 + g * 587 + b * 114) / 1000
        
        # The faux checkerboard is dark neutral gray/black
        # typically max_c < 65 and diff < 15
        if max_c < 65 and diff < 16:
            # Fully transparent
            pixdata[x, y] = (0, 0, 0, 0)
        elif max_c < 85 and diff < 22:
            # Soft edge feathering
            factor = (diff / 22.0) * (max_c / 85.0)
            alpha = int(255 * factor)
            pixdata[x, y] = (r, g, b, alpha)
        else:
            # Keep glowing neon elements & laptop
            # boost vibrancy slightly if near dark
            pixdata[x, y] = (r, g, b, 255)

img.save(dst_path, "PNG")
print(f"Successfully processed {dst_path} to transparent PNG!")
