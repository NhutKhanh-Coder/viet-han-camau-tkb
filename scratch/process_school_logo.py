from PIL import Image, ImageDraw

src = r"c:\xampp\htdocs\tkb\assets\img\logo_vkc.jpg"
dst = r"c:\xampp\htdocs\tkb\assets\img\school_logo.png"

img = Image.open(src).convert("RGBA")
width, height = img.size

# Create a circular mask for clean circular logo
mask = Image.new('L', (width, height), 0)
draw = ImageDraw.Draw(mask)
draw.ellipse((0, 0, width, height), fill=255)

# Put mask on image
output = Image.new('RGBA', (width, height), (0, 0, 0, 0))
output.paste(img, (0, 0), mask=mask)

# Save as PNG
output.save(dst, "PNG")
print(f"Created circular transparent school logo at {dst}")
