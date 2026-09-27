import sys
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')
from PIL import Image, ImageFilter
import collections

def process_ani(input_path, output_png, output_webp):
    print("Processing:", input_path)
    img = Image.open(input_path).convert("RGBA")
    w, h = img.size
    
    # Flood fill from borders
    visited = set()
    queue = collections.deque()
    
    # Outer border pixels
    for x in range(w):
        for y in [0, 1, 2, h-3, h-2, h-1]:
            r, g, b, a = img.getpixel((x, y))
            if r > 215 and g > 215 and b > 215:
                queue.append((x, y))
                visited.add((x, y))
                
    for y in range(h):
        for x in [0, 1, 2, w-3, w-2, w-1]:
            if (x, y) not in visited:
                r, g, b, a = img.getpixel((x, y))
                if r > 215 and g > 215 and b > 215:
                    queue.append((x, y))
                    visited.add((x, y))
                    
    while queue:
        cx, cy = queue.popleft()
        for dx, dy in [(-1,0), (1,0), (0,-1), (0,1)]:
            nx, ny = cx + dx, cy + dy
            if 0 <= nx < w and 0 <= ny < h and (nx, ny) not in visited:
                r, g, b, a = img.getpixel((nx, ny))
                bright = (r + g + b) / 3.0
                diff = max(abs(r-g), abs(g-b), abs(r-b))
                if bright > 220 and diff < 20:
                    visited.add((nx, ny))
                    queue.append((nx, ny))
                elif bright > 238:
                    visited.add((nx, ny))
                    queue.append((nx, ny))
                    
    print(f"Background pixels: {len(visited)} of {w*h} ({len(visited)/(w*h)*100:.1f}%)")
    
    mask = Image.new("L", (w, h), 255)
    mp = mask.load()
    for x, y in visited:
        mp[x, y] = 0
        
    feathered = mask.filter(ImageFilter.GaussianBlur(radius=1.0))
    result = img.copy()
    result.putalpha(feathered)
    
    # Clean bottom floor shadow
    pixels = result.load()
    for y in range(h - 80, h):
        for x in range(w):
            r, g, b, a = pixels[x, y]
            if a > 0:
                # Faint shadow
                if a < 210 or (r > 180 and g > 180 and b > 180):
                    pixels[x, y] = (0, 0, 0, 0)
                elif (r > 150 and g > 150 and b > 150 and a < 240):
                    pixels[x, y] = (0, 0, 0, 0)
                    
    # Also clean floating anime action lines on the right of head if any (around x > w*0.7, y < h*0.3)
    # Check connected components not connected to main body
    result.save(output_png, "PNG")
    result.save(output_webp, "WEBP", quality=95)
    print("Saved:", output_png, "and", output_webp)

base = r"C:\Users\Lê Nhựt Khánh\.gemini\antigravity-ide\brain\d4d57150-886f-4a43-bcba-500291b696d8"
out = r"c:\xampp\htdocs\tkb\assets\ai"

process_ani(base + r"\ani_grok_idle_1789832027040.jpg",
            out + r"\vutru_ani_grok_idle.png",
            out + r"\vutru_ani_grok_idle.webp")

process_ani(base + r"\ani_grok_happy_1789832264854.jpg",
            out + r"\vutru_ani_grok_happy.png",
            out + r"\vutru_ani_grok_happy.webp")
