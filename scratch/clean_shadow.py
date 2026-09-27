import sys
sys.stdout.reconfigure(encoding='utf-8')
from PIL import Image

def clean_floor_shadow(path):
    img = Image.open(path).convert("RGBA")
    w, h = img.size
    pixels = img.load()
    
    # In the bottom 60 pixels, clear any translucent shadow that isn't the boot itself (boot has purple/dark tone)
    # The shadow is faint gray/light purple with alpha < 200 or brightness > 180
    for y in range(h - 70, h):
        for x in range(w):
            r, g, b, a = pixels[x, y]
            if a > 0:
                # If it's the faint floor shadow
                if a < 200 or (r > 190 and g > 190 and b > 190):
                    pixels[x, y] = (0, 0, 0, 0)
                elif (r > 160 and g > 160 and b > 170 and a < 240):
                    pixels[x, y] = (0, 0, 0, 0)
                    
    img.save(path, "PNG")
    webp_path = path.replace(".png", ".webp")
    img.save(webp_path, "WEBP", quality=95)
    print("Cleaned shadow for:", path)

clean_floor_shadow(r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_idle.png")
clean_floor_shadow(r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_happy.png")
