import sys
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')
from PIL import Image, ImageFilter
import collections

def clean_internal_white(png_path, out_png, out_webp):
    img = Image.open(png_path).convert("RGBA")
    width, height = img.size
    pixels = img.load()
    
    # Check pixels that are still opaque (alpha == 255 or > 200) but are pure white (studio background remnants inside enclosed loops)
    # Background in studio is extremely close to pure white and neutral: r > 235, g > 235, b > 235, |r-g| < 12, |g-b| < 12
    # But wait, character has white shirt/jacket!
    # Character shirt has textures, shadows, buttons, or edges.
    # Where are enclosed loops?
    # Loop 1: between arm and body (around elbow)
    # Loop 2: between twintails and body
    # Loop 3: between legs
    
    # Let's inspect regions with white pixels that touch existing transparent pixels!
    # A dilation of transparency for 200+ pixels with pure studio white
    cleaned = img.copy()
    c_pixels = cleaned.load()
    
    # Find all fully white/neutral pixels
    # Flood fill from any pixel with brightness > 238 and neutral color that is adjacent to transparent pixel
    visited = set()
    queue = collections.deque()
    
    for y in range(height):
        for x in range(width):
            r, g, b, a = pixels[x, y]
            if a == 0:
                # check neighbors
                for dx, dy in [(-1,0), (1,0), (0,-1), (0,1)]:
                    nx, ny = x + dx, y + dy
                    if 0 <= nx < width and 0 <= ny < height:
                        nr, ng, nb, na = pixels[nx, ny]
                        if na > 100:
                            bright = (nr + ng + nb) / 3.0
                            diff = max(abs(nr-ng), abs(ng-nb), abs(nr-nb))
                            if bright > 236 and diff < 15:
                                queue.append((nx, ny))
                                visited.add((nx, ny))
                                
    print(f"Initial edge white candidates: {len(queue)}")
    while queue:
        cx, cy = queue.popleft()
        for dx, dy in [(-1,0), (1,0), (0,-1), (0,1)]:
            nx, ny = cx + dx, cy + dy
            if 0 <= nx < width and 0 <= ny < height and (nx, ny) not in visited:
                nr, ng, nb, na = pixels[nx, ny]
                bright = (nr + ng + nb) / 3.0
                diff = max(abs(nr-ng), abs(ng-nb), abs(nr-nb))
                # If it's neutral studio white and not part of the jacket with embroidery/shadow
                if bright > 238 and diff < 12:
                    visited.add((nx, ny))
                    queue.append((nx, ny))
                    
    print(f"Additional white pixels to clear: {len(visited)}")
    # Clear visited
    for x, y in visited:
        c_pixels[x, y] = (255, 255, 255, 0)
        
    cleaned.save(out_png, "PNG")
    cleaned.save(out_webp, "WEBP", quality=95)
    print("Done cleaning:", out_png)

clean_internal_white(r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_idle.png",
                     r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_idle.png",
                     r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_idle.webp")
clean_internal_white(r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_happy.png",
                     r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_happy.png",
                     r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_happy.webp")
