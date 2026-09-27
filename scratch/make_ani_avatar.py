import sys
sys.stdout.reconfigure(encoding='utf-8')
from PIL import Image

def make_ani_avatar():
    img = Image.open(r"c:\xampp\htdocs\tkb\assets\ai\vutru_ani_grok_happy.png").convert("RGBA")
    w, h = img.size
    # Head is roughly at x: 0.25 to 0.75, y: 0.05 to 0.35
    head_box = (int(w * 0.22), int(h * 0.06), int(w * 0.78), int(h * 0.36))
    cropped = img.crop(head_box)
    
    # Resize to 256x256
    cropped = cropped.resize((256, 256), Image.Resampling.LANCZOS)
    cropped.save(r"c:\xampp\htdocs\tkb\assets\ai\vutru_ani_grok_circle.png", "PNG")
    cropped.save(r"c:\xampp\htdocs\tkb\assets\ai\vutru_ani_grok_circle.webp", "WEBP", quality=95)
    print("Saved Ani avatar circle icon!")

make_ani_avatar()
